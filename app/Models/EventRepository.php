<?php

declare(strict_types=1);

namespace App\Models;

final class EventRepository extends Repository
{
    protected string $table = 'events';

    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM events
             WHERE deleted_at IS NULL
             ORDER BY event_date DESC, id DESC'
        )->fetchAll();
    }

    public function upcoming(): array
    {
        return $this->pdo->query(
            'SELECT * FROM events
             WHERE deleted_at IS NULL
               AND event_date IS NOT NULL
               AND event_date >= CURDATE()
             ORDER BY event_date ASC, id ASC'
        )->fetchAll();
    }
}
