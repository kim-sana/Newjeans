<?php

declare(strict_types=1);

namespace App\Models;

final class MemberRepository extends Repository
{
    protected string $table = 'members';

    public function allForAdmin(): array
    {
        return $this->pdo->query(
            'SELECT * FROM members
             WHERE deleted_at IS NULL
             ORDER BY id ASC'
        )->fetchAll();
    }

    public function allPublic(): array
    {
        return $this->all('id ASC');
    }
}
