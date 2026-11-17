<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use LogicException;
use PDO;

/**
 * Orders do not use soft deletes, so this repository is intentionally
 * independent of App\Models\Repository.
 */
final class OrderRepository
{
    private const ORDER_COLUMNS = [
        'user_id',
        'invoice_number',
        'access_token',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'destination_country_code',
        'destination_country',
        'destination_city_id',
        'destination_city',
        'destination_region',
        'destination_latitude',
        'destination_longitude',
        'distance_km',
        'subtotal',
        'shipping_amount',
        'total',
        'payment_provider',
        'status',
        'paid_at',
        'cancelled_at',
        'cancel_reason',
    ];

    private const ADMIN_COLUMNS = [
        'id',
        'user_id',
        'invoice_number',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'destination_country_code',
        'destination_country',
        'destination_city_id',
        'destination_city',
        'destination_region',
        'destination_latitude',
        'destination_longitude',
        'distance_km',
        'subtotal',
        'shipping_amount',
        'total',
        'payment_provider',
        'status',
        'paid_at',
        'cancelled_at',
        'cancel_reason',
        'created_at',
        'updated_at',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertOrder(array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Order data must not be empty.');
        }

        foreach (array_keys($data) as $column) {
            if (!in_array($column, self::ORDER_COLUMNS, true)) {
                throw new InvalidArgumentException('Invalid order column: ' . (string) $column);
            }
        }

        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO orders (`' . implode('`, `', $columns) . '`) VALUES (' . $placeholders . ')';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param list<array{album_id: int, album_name: string, unit_price: string, quantity: int, line_total: string}> $items
     */
    public function insertItems(int $orderId, array $items): void
    {
        if ($orderId <= 0) {
            throw new InvalidArgumentException('A valid order id is required.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('Order items must not be empty.');
        }

        $rowPlaceholders = '(?, ?, ?, ?, ?, ?)';
        $placeholders = implode(', ', array_fill(0, count($items), $rowPlaceholders));

        $sql = 'INSERT INTO order_items'
            . ' (order_id, album_id, album_name, unit_price, quantity, line_total)'
            . ' VALUES ' . $placeholders;

        $params = [];

        foreach ($items as $item) {
            $params[] = $orderId;
            $params[] = (int) $item['album_id'];
            $params[] = (string) $item['album_name'];
            $params[] = (string) $item['unit_price'];
            $params[] = (int) $item['quantity'];
            $params[] = (string) $item['line_total'];
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    public function findByToken(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE access_token = ? LIMIT 1');
        $statement->execute([$token]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Admin rows deliberately omit the guest access token.
     */
    public function findAdmin(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . implode(', ', self::ADMIN_COLUMNS) . ' FROM orders WHERE id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(int $orderId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC'
        );
        $statement->execute([$orderId]);

        return $statement->fetchAll();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function paginate(int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM orders');
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $statement = $this->pdo->prepare(
            'SELECT ' . implode(', ', self::ADMIN_COLUMNS) . ' FROM orders'
            . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );
        $statement->bindValue(1, $perPage, PDO::PARAM_INT);
        $statement->bindValue(2, $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * Must run inside an active transaction.
     */
    public function lockById(int $id): ?array
    {
        if (!$this->pdo->inTransaction()) {
            throw new LogicException('Orders must be locked inside a transaction.');
        }

        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1 FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function markPaid(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE orders SET status = 'PAID', paid_at = NOW(), updated_at = NOW()"
            . " WHERE id = ? AND status = 'PENDING_PAYMENT'"
        );
        $statement->execute([$id]);
    }

    public function markCancelled(int $id, string $reason): void
    {
        $reason = trim($reason);

        if (function_exists('mb_substr')) {
            $reason = mb_substr($reason, 0, 255, 'UTF-8');
        } else {
            $reason = substr($reason, 0, 255);
        }

        if ($reason === '') {
            $reason = 'Order cancelled.';
        }

        $statement = $this->pdo->prepare(
            "UPDATE orders SET status = 'CANCELLED', cancelled_at = NOW(), cancel_reason = ?, updated_at = NOW()"
            . " WHERE id = ? AND status = 'PENDING_PAYMENT'"
        );
        $statement->execute([$reason, $id]);
    }
}
