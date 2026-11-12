<?php

declare(strict_types=1);

namespace App\Models;

final class AlbumTypeRepository extends Repository
{
    protected string $table = 'album_types';

    public function allWithAlbumCounts(): array
    {
        return $this->pdo->query(
            'SELECT t.*,
                (SELECT COUNT(*) FROM albums a WHERE a.album_type_id = t.id AND a.deleted_at IS NULL) AS album_count
             FROM album_types t
             WHERE t.deleted_at IS NULL
             ORDER BY t.name ASC'
        )->fetchAll();
    }

    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        return $this->existsBy('name', $name, $ignoreId);
    }

    public function countActiveAlbums(int $id): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM albums WHERE album_type_id = ? AND deleted_at IS NULL'
        );
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    private function existsBy(string $column, string $value, ?int $ignoreId): bool
    {
        $sql = 'SELECT COUNT(*) FROM album_types WHERE `' . $column . '` = ?';
        $params = [$value];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }
}
