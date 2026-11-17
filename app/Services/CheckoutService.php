<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AlbumRepository;
use App\Models\OrderRepository;
use App\Payments\PaymentResult;
use App\Support\Cart;
use PDO;
use RuntimeException;
use Throwable;

final class CheckoutService
{
    private const MAX_ITEM_QUANTITY = 99;

    private PDO $pdo;

    private AlbumRepository $albums;

    private OrderRepository $orders;

    public function __construct(
        ?AlbumRepository $albums = null,
        ?OrderRepository $orders = null,
        ?PDO $pdo = null
    ) {
        $this->pdo = $pdo ?? Database::pdo();
        $this->albums = $albums ?? new AlbumRepository($this->pdo);
        $this->orders = $orders ?? new OrderRepository($this->pdo);
    }

    /**
     * Creates an order plus immutable item snapshots in one transaction.
     * Cart contents are left untouched so the caller can clear them after success.
     *
     * @param array<int, int> $cart albumId => quantity
     * @param array{recipient_name?: mixed, recipient_phone?: mixed, shipping_address?: mixed} $recipient
     * @param array{country_code?: mixed, country?: mixed, city_id?: mixed, city?: mixed, region?: mixed,
     *     latitude?: mixed, longitude?: mixed} $destination
     * @param array{distance_km?: mixed, shipping_usd?: mixed, currency?: mixed} $quote
     * @return array<string, mixed> created order row, including its `items`
     */
    public function createOrder(
        array $cart,
        array $recipient,
        array $destination,
        array $quote
    ): array {
        $quantities = $this->normalizeCart($cart);
        $address = $this->normalizeRecipient($recipient);
        $shipping = $this->normalizeDestination($destination);
        $estimate = $this->normalizeQuote($quote);

        $albums = $this->albums->findMany(array_keys($quantities));
        $items = [];
        $subtotalCents = 0;

        foreach ($quantities as $albumId => $quantity) {
            $album = $albums[$albumId] ?? null;

            if (!is_array($album)) {
                throw new RuntimeException('One or more albums in your cart are no longer available.');
            }

            if ((int) ($album['is_for_sale'] ?? 0) !== 1) {
                throw new RuntimeException('One or more albums in your cart are not available for purchase.');
            }

            if ((int) ($album['stock'] ?? 0) < $quantity) {
                throw new RuntimeException('There is not enough stock for one or more albums in your cart.');
            }

            $price = $album['price'] ?? null;

            if (!is_numeric($price) || (float) $price < 0.0) {
                throw new RuntimeException('One or more albums in your cart has an invalid price.');
            }

            $unitCents = (int) round((float) $price * 100);
            $lineCents = $unitCents * $quantity;
            $subtotalCents += $lineCents;

            $items[] = [
                'album_id' => $albumId,
                'album_name' => (string) $album['name'],
                'unit_price' => $this->formatMoney($unitCents),
                'quantity' => $quantity,
                'line_total' => $this->formatMoney($lineCents),
            ];
        }

        if ($items === []) {
            throw new RuntimeException('Your cart is empty.');
        }

        $totalCents = $subtotalCents + $this->usdToCents($estimate['shipping_usd']);
        $token = bin2hex(random_bytes(32));
        $invoice = 'NJ-' . date('Ymd') . '-' . strtoupper(substr($token, 0, 8));

        $orderData = [
            'user_id' => null,
            'invoice_number' => $invoice,
            'access_token' => $token,
            'recipient_name' => $address['recipient_name'],
            'recipient_phone' => $address['recipient_phone'],
            'shipping_address' => $address['shipping_address'],
            'destination_country_code' => $shipping['country_code'],
            'destination_country' => $shipping['country'],
            'destination_city_id' => $shipping['city_id'],
            'destination_city' => $shipping['city'],
            'destination_region' => $shipping['region'],
            'destination_latitude' => $this->formatCoordinate($shipping['latitude']),
            'destination_longitude' => $this->formatCoordinate($shipping['longitude']),
            'distance_km' => number_format($estimate['distance_km'], 2, '.', ''),
            'subtotal' => $this->formatMoney($subtotalCents),
            'shipping_amount' => $this->formatMoney($this->usdToCents($estimate['shipping_usd'])),
            'total' => $this->formatMoney($totalCents),
            'payment_provider' => 'mock',
            'status' => 'PENDING_PAYMENT',
        ];

        $this->pdo->beginTransaction();

        try {
            $orderId = $this->orders->insertOrder($orderData);
            $this->orders->insertItems($orderId, $items);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException('We could not create your order. Please try again.', 0, $exception);
        }

        $order = $this->orders->findByToken($token);

        if (!is_array($order)) {
            throw new RuntimeException('We could not load your order. Please contact support.');
        }

        $order['items'] = $this->orders->items($orderId);

        return $order;
    }

    /**
     * @param array<string, mixed> $order
     * @return array{status: string, order: array<string, mixed>, restored: int, skipped: list<string>}
     */
    public function pay(array $order, PaymentResult $result): array
    {
        if (!$result->successful()) {
            return [
                'status' => 'pending',
                'order' => $order,
                'restored' => 0,
                'skipped' => [],
            ];
        }

        $orderId = (int) ($order['id'] ?? 0);

        if ($orderId <= 0) {
            throw new RuntimeException('Order not found.');
        }

        $this->pdo->beginTransaction();

        try {
            $lockedOrder = $this->orders->lockById($orderId);

            if (!is_array($lockedOrder)) {
                $this->pdo->rollBack();

                throw new RuntimeException('Order not found.');
            }

            $currentStatus = (string) ($lockedOrder['status'] ?? '');

            if ($currentStatus === 'PAID') {
                $this->pdo->commit();

                return [
                    'status' => 'paid',
                    'order' => $this->loadOrderWithItems($lockedOrder),
                    'restored' => 0,
                    'skipped' => [],
                ];
            }

            if ($currentStatus === 'CANCELLED') {
                $this->pdo->commit();

                return [
                    'status' => 'cancelled',
                    'order' => $this->loadOrderWithItems($lockedOrder),
                    'restored' => 0,
                    'skipped' => [],
                ];
            }

            $items = $this->orders->items($orderId);

            if ($items === []) {
                $this->orders->markCancelled($orderId, 'Order has no items.');
                $this->pdo->commit();

                return [
                    'status' => 'cancelled',
                    'order' => $this->loadOrderWithItems($lockedOrder),
                    'restored' => 0,
                    'skipped' => [],
                ];
            }

            $albumIds = array_values(array_unique(array_map(
                static fn(array $item): int => (int) ($item['album_id'] ?? 0),
                $items
            )));
            sort($albumIds, SORT_NUMERIC);

            $lockedAlbums = $this->lockAlbums($albumIds);
            $stockAvailable = true;

            foreach ($items as $item) {
                $albumId = (int) ($item['album_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                $album = $lockedAlbums[$albumId] ?? null;

                if (
                    !is_array($album)
                    || $album['deleted_at'] !== null
                    || (int) ($album['is_for_sale'] ?? 0) !== 1
                    || $quantity <= 0
                    || (int) ($album['stock'] ?? 0) < $quantity
                ) {
                    $stockAvailable = false;
                    break;
                }
            }

            if (!$stockAvailable) {
                $this->orders->markCancelled(
                    $orderId,
                    'One or more albums are out of stock or no longer available.'
                );
                $this->pdo->commit();

                $restored = $this->restoreCart($lockedOrder);

                return [
                    'status' => 'unavailable',
                    'order' => $this->loadOrderWithItems($lockedOrder),
                    'restored' => $restored['restored'],
                    'skipped' => $restored['skipped'],
                ];
            }

            $decrement = $this->pdo->prepare(
                'UPDATE albums SET stock = stock - ? WHERE id = ? AND stock >= ? AND deleted_at IS NULL AND is_for_sale = 1'
            );

            foreach ($items as $item) {
                $albumId = (int) ($item['album_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                $decrement->execute([$quantity, $albumId, $quantity]);

                if ($decrement->rowCount() !== 1) {
                    $this->pdo->rollBack();

                    throw new RuntimeException('Unable to reserve album stock. Please try again.');
                }
            }

            $this->orders->markPaid($orderId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Payment could not be completed. Please try again.', 0, $exception);
        }

        return [
            'status' => 'paid',
            'order' => $this->loadOrderWithItems($lockedOrder),
            'restored' => 0,
            'skipped' => [],
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array{restored: int, skipped: list<string>}
     */
    public function restoreCart(array $order): array
    {
        $orderId = (int) ($order['id'] ?? 0);

        if ($orderId <= 0) {
            return ['restored' => 0, 'skipped' => []];
        }

        $items = $this->orders->items($orderId);

        if ($items === []) {
            return ['restored' => 0, 'skipped' => []];
        }

        $albumIds = array_values(array_unique(array_map(
            static fn(array $item): int => (int) ($item['album_id'] ?? 0),
            $items
        )));
        $albums = $this->albums->findMany($albumIds);

        $restored = 0;
        $skipped = [];

        foreach ($items as $item) {
            $albumId = (int) ($item['album_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $albumName = (string) ($item['album_name'] ?? 'Album');
            $album = $albums[$albumId] ?? null;
            $stock = is_array($album) ? (int) ($album['stock'] ?? 0) : 0;

            if (
                !is_array($album)
                || (int) ($album['is_for_sale'] ?? 0) !== 1
                || $stock <= 0
                || $quantity <= 0
            ) {
                $skipped[] = $albumName;
                continue;
            }

            Cart::add($albumId, min($quantity, $stock));
            $restored++;
        }

        return [
            'restored' => $restored,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param list<int> $albumIds
     * @return array<int, array<string, mixed>>
     */
    private function lockAlbums(array $albumIds): array
    {
        if ($albumIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($albumIds), '?'));
        $statement = $this->pdo->prepare(
            'SELECT id, name, stock, is_for_sale, deleted_at FROM albums'
            . ' WHERE id IN (' . $placeholders . ') ORDER BY id ASC FOR UPDATE'
        );
        $statement->execute($albumIds);

        $rows = [];

        foreach ($statement->fetchAll() as $row) {
            $rows[(int) $row['id']] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function loadOrderWithItems(array $order): array
    {
        $token = (string) ($order['access_token'] ?? '');
        $fresh = $token !== '' ? $this->orders->findByToken($token) : null;
        $resolved = is_array($fresh) ? $fresh : $order;
        $resolved['items'] = $this->orders->items((int) ($resolved['id'] ?? 0));

        return $resolved;
    }

    /**
     * @param array<mixed> $cart
     * @return array<int, int> albumId => quantity, sorted by album id
     */
    private function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $albumId => $quantity) {
            $id = (int) $albumId;
            $amount = (int) $quantity;

            if ($id <= 0 || $amount <= 0 || $amount > self::MAX_ITEM_QUANTITY) {
                throw new RuntimeException('Your cart contains an invalid item.');
            }

            $normalized[$id] = $amount;
        }

        if ($normalized === []) {
            throw new RuntimeException('Your cart is empty.');
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $recipient
     * @return array{recipient_name: string, recipient_phone: string, shipping_address: string}
     */
    private function normalizeRecipient(array $recipient): array
    {
        $name = $this->stringField($recipient['recipient_name'] ?? null);
        $phone = $this->stringField($recipient['recipient_phone'] ?? null);
        $address = $this->stringField($recipient['shipping_address'] ?? null);

        if ($name === '' || $this->length($name) > 120) {
            throw new RuntimeException('Enter a valid recipient name.');
        }

        if (
            $this->length($phone) < 7
            || $this->length($phone) > 40
            || preg_match('/^[0-9+()\-\s]+$/', $phone) !== 1
        ) {
            throw new RuntimeException('Enter a valid recipient phone number.');
        }

        if ($address === '' || $this->length($address) > 255) {
            throw new RuntimeException('Enter a valid shipping address.');
        }

        return [
            'recipient_name' => $name,
            'recipient_phone' => $phone,
            'shipping_address' => $address,
        ];
    }

    /**
     * @param array<string, mixed> $destination
     * @return array{country_code: string, country: string, city_id: int, city: string,
     *     region: ?string, latitude: float, longitude: float}
     */
    private function normalizeDestination(array $destination): array
    {
        $countryCode = strtoupper($this->stringField($destination['country_code'] ?? null));
        $country = $this->stringField($destination['country'] ?? null);
        $city = $this->stringField($destination['city'] ?? null);
        $cityIdValue = $destination['city_id'] ?? null;
        $cityId = is_numeric($cityIdValue) ? (int) $cityIdValue : 0;
        $regionValue = $destination['region'] ?? null;
        $region = is_string($regionValue) ? trim($regionValue) : null;

        $latitude = $destination['latitude'] ?? null;
        $longitude = $destination['longitude'] ?? null;

        if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        if ($country === '' || $this->length($country) > 120) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        if ($cityId <= 0 || $city === '' || $this->length($city) > 160) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        if ($region === '') {
            $region = null;
        }

        if ($region !== null && $this->length($region) > 160) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            throw new RuntimeException('Choose a valid shipping destination.');
        }

        return [
            'country_code' => $countryCode,
            'country' => $country,
            'city_id' => $cityId,
            'city' => $city,
            'region' => $region,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /**
     * @param array<string, mixed> $quote
     * @return array{distance_km: float, shipping_usd: float}
     */
    private function normalizeQuote(array $quote): array
    {
        $distance = $quote['distance_km'] ?? null;
        $shipping = $quote['shipping_usd'] ?? null;

        if (
            !is_numeric($distance)
            || !is_numeric($shipping)
            || (float) $distance < 0.0
            || (float) $shipping < 0.0
        ) {
            throw new RuntimeException('Shipping estimates are not available. Please try again later.');
        }

        return [
            'distance_km' => (float) $distance,
            'shipping_usd' => (float) $shipping,
        ];
    }

    private function stringField(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function usdToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function formatMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function formatCoordinate(float $value): string
    {
        return number_format($value, 6, '.', '');
    }
}
