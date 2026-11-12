<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;
use RuntimeException;

final class Slug
{
    public static function make(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'item';
        }

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

            if (is_string($transliterated) && $transliterated !== '') {
                $value = $transliterated;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'item' : $value;
    }

    public static function unique(string $table, string $source, ?int $ignoreId = null, int $maxLength = 190): string
    {
        self::assertTable($table);

        $base = substr(self::make($source), 0, max(1, $maxLength - 4));
        $candidate = $base;
        $suffix = 2;

        while (self::exists($table, $candidate, $ignoreId)) {
            $suffixText = '-' . $suffix;
            $candidate = substr($base, 0, max(1, $maxLength - strlen($suffixText))) . $suffixText;
            $suffix++;

            if ($suffix > 9999) {
                throw new RuntimeException('Unable to generate a unique slug.');
            }
        }

        return $candidate;
    }

    private static function exists(string $table, string $slug, ?int $ignoreId): bool
    {
        $sql = 'SELECT COUNT(*) FROM `' . $table . '` WHERE slug = ?';
        $params = [$slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $statement = Database::pdo()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    private static function assertTable(string $table): void
    {
        if (preg_match('/^[a-z_]+$/', $table) !== 1) {
            throw new RuntimeException('Invalid slug table.');
        }
    }
}
