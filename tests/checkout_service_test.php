<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\OrderRepository;
use App\Services\CheckoutService;
use App\Shipping\DestinationCatalog;
use App\Shipping\ShippingCalculator;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$testDb = (string) env('NJ_TEST_DB', 'newjeans_purchase_test');

if (stripos($testDb, 'test') === false) {
    fwrite(STDERR, 'Refusing to run: NJ_TEST_DB must contain "test".' . PHP_EOL);
    exit(1);
}

$_ENV['DB_NAME'] = $testDb;
$_SERVER['DB_NAME'] = $testDb;
putenv('DB_NAME=' . $testDb);

$pdo = Database::pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$checks = 0;
$failures = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $checks, $failures;

    $checks++;

    if ($condition) {
        echo 'PASS  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;

        return;
    }

    $failures++;
    echo 'FAIL  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;
}

function expectRuntimeException(string $label, callable $callback, string $needle = ''): void
{
    try {
        $callback();
    } catch (RuntimeException $exception) {
        $contains = $needle === '' || stripos($exception->getMessage(), $needle) !== false;
        check($label, $contains, $exception->getMessage());

        return;
    } catch (Throwable $exception) {
        check($label, false, get_class($exception) . ': ' . $exception->getMessage());

        return;
    }

    check($label, false, 'no RuntimeException thrown');
}

function orderCount(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
}

function itemCount(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM order_items')->fetchColumn();
}

echo 'Database: ' . $testDb . PHP_EOL;

$tables = $pdo->query("SHOW TABLES LIKE 'order%'")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('orders', $tables, true) || !in_array('order_items', $tables, true)) {
    fwrite(STDERR, 'Missing orders/order_items tables in ' . $testDb . '. Import database/schema.sql first.' . PHP_EOL);
    exit(1);
}

$albumIds = [1, 2, 3, 6];
$placeholders = implode(', ', array_fill(0, count($albumIds), '?'));
$statement = $pdo->prepare(
    'SELECT id, name, price, stock, is_for_sale, deleted_at FROM albums WHERE id IN (' . $placeholders . ')'
);
$statement->execute($albumIds);
$snapshots = [];

foreach ($statement->fetchAll() as $row) {
    $snapshots[(int) $row['id']] = $row;
}

if (count($snapshots) !== count($albumIds)) {
    fwrite(STDERR, 'Expected seeded albums 1, 2, 3, and 6 in ' . $testDb . '.' . PHP_EOL);
    exit(1);
}

$restoreAlbum = static function (int $id) use ($pdo, $snapshots): void {
    $snapshot = $snapshots[$id];
    $update = $pdo->prepare('UPDATE albums SET price = ?, stock = ?, is_for_sale = ?, deleted_at = ? WHERE id = ?');
    $update->execute([
        $snapshot['price'],
        $snapshot['stock'],
        $snapshot['is_for_sale'],
        $snapshot['deleted_at'],
        $id,
    ]);
};

$createdOrderIds = [];
$triggerName = 'nj_test_fail_order_items';

$catalog = new DestinationCatalog();
$jakarta = $catalog->findCity('ID', 1642911);

if (!is_array($jakarta)) {
    fwrite(STDERR, 'Jakarta is missing from the shipping dataset.' . PHP_EOL);
    exit(1);
}

$calculator = new ShippingCalculator(['base_fee_usd' => '3.00', 'fee_per_km_usd' => '0.0015']);
$quote = $calculator->quote($jakarta['lat'], $jakarta['lng']);

if (!is_array($quote)) {
    fwrite(STDERR, 'Shipping quote could not be calculated.' . PHP_EOL);
    exit(1);
}

$recipient = [
    'recipient_name' => 'Budi Santoso',
    'recipient_phone' => '+62 812 3456 7890',
    'shipping_address' => 'Jl. Sudirman No. 1',
];
$destination = [
    'country_code' => 'ID',
    'country' => 'Indonesia',
    'city_id' => 1642911,
    'city' => 'Jakarta',
    'region' => $jakarta['region'],
    'latitude' => $jakarta['lat'],
    'longitude' => $jakarta['lng'],
];

$service = new CheckoutService(null, null, $pdo);
$orders = new OrderRepository($pdo);

try {
    echo PHP_EOL . 'Successful order' . PHP_EOL;

    check('empty cart is rejected', (static function () use ($service, $recipient, $destination, $quote): bool {
        try {
            $service->createOrder([], $recipient, $destination, $quote);
        } catch (RuntimeException $exception) {
            return $exception->getMessage() === 'Your cart is empty.';
        }

        return false;
    })());

    $before = orderCount($pdo);
    $order = $service->createOrder([1 => 2, 6 => 1], $recipient, $destination, $quote);
    $createdOrderIds[] = (int) $order['id'];

    check('one order row was inserted', orderCount($pdo) === $before + 1);
    check('status starts as PENDING_PAYMENT', $order['status'] === 'PENDING_PAYMENT', (string) $order['status']);
    check('payment provider is mock', $order['payment_provider'] === 'mock', (string) $order['payment_provider']);
    check('guest order has no user id', $order['user_id'] === null);
    check(
        'access token is 64 lowercase hex characters',
        preg_match('/^[a-f0-9]{64}$/', (string) $order['access_token']) === 1
    );
    check(
        'invoice number follows NJ-YYYYMMDD-XXXXXXXX',
        preg_match('/^NJ-\d{8}-[A-F0-9]{8}$/', (string) $order['invoice_number']) === 1,
        (string) $order['invoice_number']
    );
    check(
        'invoice suffix matches the token prefix',
        str_ends_with((string) $order['invoice_number'], strtoupper(substr((string) $order['access_token'], 0, 8)))
    );

    $expectedSubtotal = 2 * 2499 + 999;
    $expectedShipping = (int) round((float) $quote['shipping_usd'] * 100);
    $expectedTotal = $expectedSubtotal + $expectedShipping;

    check('subtotal snapshot is correct', $order['subtotal'] === number_format($expectedSubtotal / 100, 2, '.', ''), (string) $order['subtotal']);
    check('shipping snapshot is correct', $order['shipping_amount'] === number_format($expectedShipping / 100, 2, '.', ''), (string) $order['shipping_amount']);
    check('total snapshot is correct', $order['total'] === number_format($expectedTotal / 100, 2, '.', ''), (string) $order['total']);
    check(
        'distance snapshot is rounded to two decimals',
        $order['distance_km'] === number_format((float) $quote['distance_km'], 2, '.', ''),
        (string) $order['distance_km']
    );
    check('destination country is snapshotted', $order['destination_country'] === 'Indonesia');
    check('destination city is snapshotted', $order['destination_city'] === 'Jakarta');
    check('destination city id is snapshotted', (int) $order['destination_city_id'] === 1642911);
    check(
        'destination coordinates are snapshotted',
        abs((float) $order['destination_latitude'] - (float) $jakarta['lat']) < 0.000001
        && abs((float) $order['destination_longitude'] - (float) $jakarta['lng']) < 0.000001
    );
    check('recipient name is snapshotted', $order['recipient_name'] === 'Budi Santoso');
    check('recipient phone is snapshotted', $order['recipient_phone'] === '+62 812 3456 7890');
    check('shipping address is snapshotted', $order['shipping_address'] === 'Jl. Sudirman No. 1');

    check('returned order includes items', isset($order['items']) && count($order['items']) === 2);
    $items = is_array($order['items'] ?? null) ? $order['items'] : [];

    check('item snapshots are ordered by album id', array_column($items, 'album_id') === [1, 6]);
    check('first item name snapshot is correct', ($items[0]['album_name'] ?? '') === 'How Sweet');
    check('first item unit price snapshot is correct', ($items[0]['unit_price'] ?? '') === '24.99');
    check('first item quantity snapshot is correct', (int) ($items[0]['quantity'] ?? 0) === 2);
    check('first item line total is correct', ($items[0]['line_total'] ?? '') === '49.98');
    check('second item name snapshot is correct', ($items[1]['album_name'] ?? '') === 'Gods');
    check('second item unit price snapshot is correct', ($items[1]['unit_price'] ?? '') === '9.99');
    check('second item line total is correct', ($items[1]['line_total'] ?? '') === '9.99');

    check(
        'order creation does not decrement stock',
        (int) $pdo->query('SELECT stock FROM albums WHERE id = 1')->fetchColumn() === 40
        && (int) $pdo->query('SELECT stock FROM albums WHERE id = 6')->fetchColumn() === 60
    );

    check('findByToken rejects malformed tokens', $orders->findByToken('not-a-token') === null);
    check('findByToken finds the created order', ($orders->findByToken((string) $order['access_token'])['id'] ?? 0) === (int) $order['id']);

    $adminRow = $orders->findAdmin((int) $order['id']);
    check('findAdmin returns the order', is_array($adminRow));
    check('findAdmin omits the access token', is_array($adminRow) && !array_key_exists('access_token', $adminRow));

    $page = $orders->paginate(1);
    check('paginate reports a positive total', $page['total'] >= 1, (string) $page['total']);
    check('paginate uses 20 rows per page', $page['per_page'] === 20);
    check(
        'paginate includes the newest order',
        in_array((int) $order['id'], array_map(static fn (array $row): int => (int) $row['id'], $page['rows']), true)
    );
    check(
        'paginate rows omit the access token',
        count(array_filter($page['rows'], static fn (array $row): bool => array_key_exists('access_token', $row))) === 0
    );

    check(
        'lockById requires a transaction',
        (static function () use ($orders, $order): bool {
            try {
                $orders->lockById((int) $order['id']);
            } catch (LogicException) {
                return true;
            }

            return false;
        })()
    );

    $pdo->beginTransaction();
    $locked = $orders->lockById((int) $order['id']);
    $pdo->rollBack();
    check('lockById returns the row inside a transaction', is_array($locked) && (int) $locked['id'] === (int) $order['id']);

    echo PHP_EOL . 'Uniqueness' . PHP_EOL;

    $second = $service->createOrder([1 => 1], $recipient, $destination, $quote);
    $createdOrderIds[] = (int) $second['id'];

    check('a second order gets a different token', $second['access_token'] !== $order['access_token']);
    check('a second order gets a different invoice number', $second['invoice_number'] !== $order['invoice_number']);
    check('the second order snapshots its own items', count($second['items']) === 1 && (int) $second['items'][0]['quantity'] === 1);

    echo PHP_EOL . 'Album validation' . PHP_EOL;

    $pdo->prepare('UPDATE albums SET stock = 1 WHERE id = 2')->execute();
    $before = orderCount($pdo);
    expectRuntimeException(
        'insufficient stock throws and writes nothing',
        static fn () => $service->createOrder([2 => 2], $recipient, $destination, $quote),
        'stock'
    );
    check('insufficient stock leaves the order count unchanged', orderCount($pdo) === $before);
    $restoreAlbum(2);

    $pdo->prepare('UPDATE albums SET is_for_sale = 0 WHERE id = 3')->execute();
    $before = orderCount($pdo);
    expectRuntimeException(
        'not-for-sale albums are rejected',
        static fn () => $service->createOrder([3 => 1], $recipient, $destination, $quote),
        'not available for purchase'
    );
    check('not-for-sale rejection writes nothing', orderCount($pdo) === $before);
    $restoreAlbum(3);

    $pdo->prepare('UPDATE albums SET deleted_at = NOW() WHERE id = 3')->execute();
    $before = orderCount($pdo);
    expectRuntimeException(
        'soft-deleted albums are rejected',
        static fn () => $service->createOrder([3 => 1], $recipient, $destination, $quote),
        'no longer available'
    );
    check('soft-deleted rejection writes nothing', orderCount($pdo) === $before);
    $restoreAlbum(3);

    $pdo->prepare('UPDATE albums SET price = -1.00 WHERE id = 3')->execute();
    $before = orderCount($pdo);
    expectRuntimeException(
        'negative prices are rejected',
        static fn () => $service->createOrder([3 => 1], $recipient, $destination, $quote),
        'invalid price'
    );
    check('negative price rejection writes nothing', orderCount($pdo) === $before);
    $restoreAlbum(3);

    $invalidQuantity = [1 => 100];
    expectRuntimeException(
        'quantities above the cap are rejected',
        static fn () => $service->createOrder($invalidQuantity, $recipient, $destination, $quote),
        'invalid item'
    );

    echo PHP_EOL . 'Payload validation' . PHP_EOL;

    $before = orderCount($pdo);

    expectRuntimeException(
        'a missing recipient name is rejected',
        static fn () => $service->createOrder([1 => 1], ['recipient_phone' => '+62 812 3456 7890', 'shipping_address' => 'Street'], $destination, $quote),
        'recipient name'
    );
    expectRuntimeException(
        'a short phone number is rejected',
        static fn () => $service->createOrder([1 => 1], ['recipient_name' => 'Budi', 'recipient_phone' => '123', 'shipping_address' => 'Street'], $destination, $quote),
        'phone'
    );
    expectRuntimeException(
        'an invalid country code is rejected',
        static fn () => $service->createOrder([1 => 1], $recipient, array_merge($destination, ['country_code' => 'IDN']), $quote),
        'destination'
    );
    expectRuntimeException(
        'a missing city is rejected',
        static fn () => $service->createOrder([1 => 1], $recipient, array_merge($destination, ['city' => '']), $quote),
        'destination'
    );
    expectRuntimeException(
        'a negative shipping quote is rejected',
        static fn () => $service->createOrder([1 => 1], $recipient, $destination, array_merge($quote, ['shipping_usd' => -1])),
        'Shipping estimates'
    );

    check('invalid payloads write nothing', orderCount($pdo) === $before);

    echo PHP_EOL . 'Transaction rollback' . PHP_EOL;

    $pdo->exec('DROP TRIGGER IF EXISTS ' . $triggerName);
    $pdo->exec(
        'CREATE TRIGGER ' . $triggerName . ' BEFORE INSERT ON order_items FOR EACH ROW '
        . "BEGIN IF NEW.album_id = 6 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forced item insert failure'; END IF; END"
    );

    $beforeOrders = orderCount($pdo);
    $beforeItems = itemCount($pdo);
    expectRuntimeException(
        'a failed item insert rolls the order back',
        static fn () => $service->createOrder([1 => 1, 6 => 1], $recipient, $destination, $quote),
        'could not create your order'
    );
    check('rollback leaves no partial order row', orderCount($pdo) === $beforeOrders);
    check('rollback leaves no partial item rows', itemCount($pdo) === $beforeItems);

    $pdo->exec('DROP TRIGGER IF EXISTS ' . $triggerName);
    check('the rollback trigger was removed', true);
} finally {
    try {
        $pdo->exec('DROP TRIGGER IF EXISTS ' . $triggerName);
    } catch (Throwable) {
        // Ignore cleanup failures.
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ([1, 2, 3, 6] as $id) {
        $restoreAlbum($id);
    }

    if ($createdOrderIds !== []) {
        $list = implode(', ', array_fill(0, count($createdOrderIds), '?'));
        $delete = $pdo->prepare('DELETE FROM orders WHERE id IN (' . $list . ')');
        $delete->execute($createdOrderIds);
    }
}

echo PHP_EOL;
echo $failures === 0
    ? 'OK (' . $checks . ' checks)' . PHP_EOL
    : 'FAILED (' . $failures . ' of ' . $checks . ' checks)' . PHP_EOL;

exit($failures === 0 ? 0 : 1);
