<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class NewsRepository extends Repository
{
    protected string $table = 'news';

    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM news
             WHERE deleted_at IS NULL
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC'
        )->fetchAll();
    }

    public function published(?int $limit = null): array
    {
        $sql = 'SELECT * FROM news
                WHERE status = \'published\'
                  AND deleted_at IS NULL
                  AND published_at IS NOT NULL
                  AND published_at <= NOW()
                ORDER BY published_at DESC, id DESC';

        if ($limit === null) {
            return $this->pdo->query($sql)->fetchAll();
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT ?');
        $statement->bindValue(1, max(0, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM news
             WHERE slug = ?
               AND status = \'published\'
               AND deleted_at IS NULL
               AND published_at IS NOT NULL
               AND published_at <= NOW()
             LIMIT 1'
        );
        $statement->execute([$slug]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function relatedPublished(string $slug, int $limit = 3): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM news
             WHERE slug <> ?
               AND status = \'published\'
               AND deleted_at IS NULL
               AND published_at IS NOT NULL
               AND published_at <= NOW()
             ORDER BY published_at DESC, id DESC
             LIMIT ?'
        );
        $statement->bindValue(1, $slug);
        $statement->bindValue(2, max(0, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
