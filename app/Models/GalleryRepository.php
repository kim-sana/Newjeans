<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class GalleryRepository extends Repository
{
    protected string $table = 'gallery_items';

    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM gallery_items
             WHERE deleted_at IS NULL
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    }

    public function publicItems(?int $limit = null): array
    {
        $sql = 'SELECT * FROM gallery_items
                WHERE deleted_at IS NULL
                ORDER BY sort_order ASC, id ASC';

        if ($limit === null) {
            return $this->pdo->query($sql)->fetchAll();
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT ?');
        $statement->bindValue(1, max(0, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function nextSortOrder(): int
    {
        return (int) $this->pdo->query(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_items WHERE deleted_at IS NULL'
        )->fetchColumn();
    }
}
