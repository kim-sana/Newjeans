<?php

declare(strict_types=1);

namespace App\Models;

final class HistoryRepository extends Repository
{
    protected string $table = 'histories';

    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM histories
             WHERE deleted_at IS NULL
             ORDER BY event_date ASC, id ASC'
        )->fetchAll();
    }

    public function allPublic(): array
    {
        return $this->all('event_date ASC, id ASC');
    }
}
