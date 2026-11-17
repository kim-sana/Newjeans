<?php

declare(strict_types=1);

namespace App\Support;

final class Cart
{
    private const SESSION_KEY = 'cart';
    private const MAX_QUANTITY = 99;

    /**
     * @return array<int, int> albumId => quantity
     */
    public static function items(): array
    {
        session_start_safe();

        $raw = $_SESSION[self::SESSION_KEY] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $albumId => $quantity) {
            $id = self::normalizeId($albumId);
            $amount = self::normalizeQuantity($quantity);

            if ($id === null || $amount === null) {
                continue;
            }

            $items[$id] = $amount;
        }

        ksort($items);

        return $items;
    }

    public static function add(int $albumId, int $quantity = 1): void
    {
        if ($albumId <= 0 || $quantity <= 0) {
            return;
        }

        $items = self::items();
        $current = $items[$albumId] ?? 0;

        $items[$albumId] = min($current + $quantity, self::MAX_QUANTITY);

        self::persist($items);
    }

    public static function setQuantity(int $albumId, int $quantity): void
    {
        if ($albumId <= 0) {
            return;
        }

        $items = self::items();

        if ($quantity <= 0) {
            unset($items[$albumId]);
        } else {
            $items[$albumId] = min($quantity, self::MAX_QUANTITY);
        }

        self::persist($items);
    }

    public static function remove(int $albumId): void
    {
        if ($albumId <= 0) {
            return;
        }

        $items = self::items();
        unset($items[$albumId]);

        self::persist($items);
    }

    public static function clear(): void
    {
        session_start_safe();

        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    public static function isEmpty(): bool
    {
        return self::items() === [];
    }

    /**
     * @param array<int, int> $items
     */
    private static function persist(array $items): void
    {
        session_start_safe();

        if ($items === []) {
            unset($_SESSION[self::SESSION_KEY]);

            return;
        }

        ksort($items);

        $_SESSION[self::SESSION_KEY] = $items;
    }

    private static function normalizeId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private static function normalizeQuantity(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? min($value, self::MAX_QUANTITY) : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $quantity = (int) $value;

            return $quantity > 0 ? min($quantity, self::MAX_QUANTITY) : null;
        }

        return null;
    }
}
