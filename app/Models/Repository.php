<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

abstract class Repository
{
    protected string $table = '';

    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM `' . $this->table . '` WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        $this->assertOrderBy($orderBy);

        return $this->pdo
            ->query('SELECT * FROM `' . $this->table . '` WHERE deleted_at IS NULL ORDER BY ' . $orderBy)
            ->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $this->assertColumns($columns);

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`, `', $columns) . '`, created_at)'
            . ' VALUES (' . $placeholders . ', NOW())';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $columns = array_keys($data);
        $this->assertColumns($columns);

        $assignments = implode(', ', array_map(
            static fn (string $column): string => '`' . $column . '` = ?',
            $columns
        ));

        $params = array_values($data);
        $params[] = $id;

        $statement = $this->pdo->prepare(
            'UPDATE `' . $this->table . '` SET ' . $assignments . ' WHERE id = ? AND deleted_at IS NULL'
        );

        return $statement->execute($params);
    }

    public function softDelete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . $this->table . '` SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL'
        );
        $statement->execute([$id]);

        return $statement->rowCount() > 0;
    }

    protected function assertColumns(array $columns): void
    {
        foreach ($columns as $column) {
            if (preg_match('/^[a-z_]+$/', (string) $column) !== 1) {
                throw new InvalidArgumentException('Invalid repository column.');
            }
        }
    }

    protected function assertOrderBy(string $orderBy): void
    {
        if (preg_match('/^[a-z_]+( (ASC|DESC))?(, [a-z_]+( (ASC|DESC))?)*$/i', $orderBy) !== 1) {
            throw new InvalidArgumentException('Invalid repository order clause.');
        }
    }
}
