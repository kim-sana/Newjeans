<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class SongRepository extends Repository
{
    protected string $table = 'songs';

    public function featuredWithAlbum(int $limit = 3): array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.*, a.name AS album_name, a.cover_album, a.release_date,
                    a.spotify_url, a.youtube_url, t.name AS type_name, t.slug AS type_slug
             FROM songs s
             JOIN albums a ON a.id = s.album_id
             JOIN album_types t ON t.id = a.album_type_id
             WHERE s.is_featured = 1
               AND s.deleted_at IS NULL
               AND a.deleted_at IS NULL
               AND t.deleted_at IS NULL
             ORDER BY a.release_date ASC, s.track_number ASC, s.id ASC
             LIMIT ?'
        );
        $statement->bindValue(1, max(0, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function forAlbum(int $albumId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM songs
             WHERE album_id = ? AND deleted_at IS NULL
             ORDER BY track_number ASC, id ASC'
        );
        $statement->execute([$albumId]);

        return $statement->fetchAll();
    }

    public function findForAlbum(int $albumId, int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM songs
             WHERE id = ? AND album_id = ? AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([$id, $albumId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }
}
