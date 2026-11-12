<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class AlbumRepository extends Repository
{
    protected string $table = 'albums';

    public function allWithTypeAndSongCount(): array
    {
        return $this->pdo->query(
            'SELECT a.*, t.name AS type_name, t.slug AS type_slug,
                (SELECT COUNT(*) FROM songs s WHERE s.album_id = a.id AND s.deleted_at IS NULL) AS song_count
             FROM albums a
             JOIN album_types t ON t.id = a.album_type_id
             WHERE a.deleted_at IS NULL
             ORDER BY a.release_date DESC, a.id DESC'
        )->fetchAll();
    }

    public function findWithType(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.*, t.name AS type_name, t.slug AS type_slug
             FROM albums a
             JOIN album_types t ON t.id = a.album_type_id
             WHERE a.id = ? AND a.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function othersByType(int $typeId, int $excludeId, int $limit = 3): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.*, t.name AS type_name, t.slug AS type_slug,
                (SELECT COUNT(*) FROM songs s WHERE s.album_id = a.id AND s.deleted_at IS NULL) AS song_count
             FROM albums a
             JOIN album_types t ON t.id = a.album_type_id
             WHERE a.album_type_id = ?
               AND a.id <> ?
               AND a.deleted_at IS NULL
               AND t.deleted_at IS NULL
             ORDER BY a.release_date DESC, a.id DESC
             LIMIT ?'
        );
        $statement->bindValue(1, $typeId, PDO::PARAM_INT);
        $statement->bindValue(2, $excludeId, PDO::PARAM_INT);
        $statement->bindValue(3, max(0, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countActiveSongs(int $id): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM songs WHERE album_id = ? AND deleted_at IS NULL'
        );
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }
}
