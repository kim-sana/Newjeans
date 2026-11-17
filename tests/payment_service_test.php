<?php

declare(strict_types=1);

use App\Core\Database;
use App\Payments\MockPaymentGateway;
use App\Services\CheckoutService;
use App\Support\Cart;
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
session_start_safe();

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

function makeOrder(CheckoutService $service, array $cart): array
{
    return $service->createOrder(
        $cart,
        [
            'recipient_name' => 'Minji Kim',
            'recipient_phone' => '+82 10-1234-5678',
            'shipping_address' => '123 Hangang-daero, Yongsan-gu',
        ],
        [
            'country_code' => 'KR',
            'country' => 'South Korea',
            'city_id' => 1835848,
            'city' => 'Seoul',
            'region' => 'Seoul',
            'latitude' => 37.5665,
            'longitude' => 126.9780,
        ],
        [
            'distance_km' => 0.07,
            'shipping_usd' => 3.00,
            'currency' => 'USD',
        ]
    );
}

$albums = $pdo->query(
    'SELECT id, name, stock, is_for_sale FROM albums WHERE deleted_at IS NULL AND is_for_sale = 1 ORDER BY id ASC LIMIT 2'
)->fetchAll(PDO::FETCH_ASSOC);

if (count($albums) < 2) {
    fwrite(STDERR, 'Need at least 2 active for-sale albums in ' . $testDb . PHP_EOL);
    exit(1);
}

$albumA = (int) $albums[0]['id'];
$albumB = (int) $albums[1]['id'];
$origStockA = (int) $albums[0]['stock'];
$origStockB = (int) $albums[1]['stock'];

$createdOrderIds = [];
$gateway = new MockPaymentGateway();
$service = new CheckoutService(null, null, $pdo);

try {
    // 1. Gateway contract
    check('gateway provider is mock', $gateway->provider() === 'mock');
    $failResult = $gateway->simulate('failure');
    $okResult = $gateway->simulate('success');
    check('failure result is unsuccessful', !$failResult->successful() && $failResult->reference() === null);
    check('success result is successful with reference', $okResult->successful() && is_string($okResult->reference()));

    // Set deterministic stocks
    $pdo->prepare('UPDATE albums SET stock = ? WHERE id = ?')->execute([10, $albumA]);
    $pdo->prepare('UPDATE albums SET stock = ? WHERE id = ?')->execute([5, $albumB]);

    // 2. Success marks paid and decrements once
    $order1 = makeOrder($service, [$albumA => 2]);
    $createdOrderIds[] = (int) $order1['id'];

    $res1 = $service->pay($order1, $gateway->simulate('success'));
    $stockAfter1 = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumA)->fetchColumn();

    check('pay success status is paid', $res1['status'] === 'paid');
    check('order1 DB status is PAID', $res1['order']['status'] === 'PAID');
    check('order1 paid_at is set', !empty($res1['order']['paid_at']));
    check('stock decremented from 10 to 8', $stockAfter1 === 8, 'stock=' . $stockAfter1);

    // 3. Repeated success is idempotent
    $res1Again = $service->pay($order1, $gateway->simulate('success'));
    $stockAfter1Again = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumA)->fetchColumn();

    check('repeated success returns paid', $res1Again['status'] === 'paid');
    check('repeated success does not decrement stock again', $stockAfter1Again === 8, 'stock=' . $stockAfter1Again);

    // 4. Failure leaves pending and then success pays once
    $order2 = makeOrder($service, [$albumA => 1]);
    $createdOrderIds[] = (int) $order2['id'];

    $res2Fail = $service->pay($order2, $gateway->simulate('failure'));
    $stockAfterFail = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumA)->fetchColumn();
    $statusAfterFail = (string) $pdo->query('SELECT status FROM orders WHERE id = ' . (int) $order2['id'])->fetchColumn();

    check('failure returns pending', $res2Fail['status'] === 'pending');
    check('failure keeps DB status PENDING_PAYMENT', $statusAfterFail === 'PENDING_PAYMENT');
    check('failure does not change stock', $stockAfterFail === 8);

    $res2Retry = $service->pay($order2, $gateway->simulate('success'));
    $stockAfterRetry = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumA)->fetchColumn();

    check('retry success marks paid', $res2Retry['status'] === 'paid' && $res2Retry['order']['status'] === 'PAID');
    check('retry success decrements stock once to 7', $stockAfterRetry === 7, 'stock=' . $stockAfterRetry);

    // 5. Stock unavailable cancels order and restores available cart items
    Cart::clear();
    $order3 = makeOrder($service, [$albumA => 2, $albumB => 3]);
    $createdOrderIds[] = (int) $order3['id'];

    // Drop albumB stock to 1 so order3 (needs 3) becomes unavailable, while albumA still has 7
    $pdo->prepare('UPDATE albums SET stock = 1 WHERE id = ?')->execute([$albumB]);

    $res3 = $service->pay($order3, $gateway->simulate('success'));
    $stockAAfter3 = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumA)->fetchColumn();
    $stockBAfter3 = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumB)->fetchColumn();

    check('unavailable pay returns unavailable', $res3['status'] === 'unavailable');
    check('unavailable order marked CANCELLED', $res3['order']['status'] === 'CANCELLED');
    check('unavailable order has cancelled_at', !empty($res3['order']['cancelled_at']));
    check(
        'unavailable cancel_reason mentions stock',
        stripos((string) ($res3['order']['cancel_reason'] ?? ''), 'stock') !== false
    );
    check('unavailable does not decrement albumA stock', $stockAAfter3 === 7);
    check('unavailable does not decrement albumB stock', $stockBAfter3 === 1);
    check('restoreCart restored both available items', $res3['restored'] === 2 && $res3['skipped'] === []);
    $cartItems = Cart::items();
    check('cart restored albumA=2 and albumB=1 (capped by stock)', ($cartItems[$albumA] ?? 0) === 2 && ($cartItems[$albumB] ?? 0) === 1);

    // 6. Two orders competing for the last copy
    Cart::clear();
    $pdo->prepare('UPDATE albums SET stock = 1 WHERE id = ?')->execute([$albumB]);

    $race1 = makeOrder($service, [$albumB => 1]);
    $race2 = makeOrder($service, [$albumB => 1]);
    $createdOrderIds[] = (int) $race1['id'];
    $createdOrderIds[] = (int) $race2['id'];

    $raceRes1 = $service->pay($race1, $gateway->simulate('success'));
    $raceRes2 = $service->pay($race2, $gateway->simulate('success'));
    $finalStockB = (int) $pdo->query('SELECT stock FROM albums WHERE id = ' . $albumB)->fetchColumn();

    check('first competing order pays', $raceRes1['status'] === 'paid' && $raceRes1['order']['status'] === 'PAID');
    check('second competing order cancels without overselling', $raceRes2['status'] === 'unavailable' && $raceRes2['order']['status'] === 'CANCELLED');
    check('stock never goes negative (remains 0)', $finalStockB === 0, 'stock=' . $finalStockB);
} finally {
    Cart::clear();

    if ($createdOrderIds !== []) {
        $placeholders = implode(', ', array_fill(0, count($createdOrderIds), '?'));
        $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders)")->execute($createdOrderIds);
    }

    $pdo->prepare('UPDATE albums SET stock = ? WHERE id = ?')->execute([$origStockA, $albumA]);
    $pdo->prepare('UPDATE albums SET stock = ? WHERE id = ?')->execute([$origStockB, $albumB]);
}

echo PHP_EOL . sprintf('Summary: %d passed, %d failed out of %d checks.', $checks - $failures, $failures, $checks) . PHP_EOL;
exit($failures === 0 ? 0 : 1);
