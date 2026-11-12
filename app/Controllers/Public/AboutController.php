<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\HistoryRepository;
use App\Models\MemberRepository;

final class AboutController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderPage('pages/about', [
            'histories' => (new HistoryRepository())->allPublic(),
            'members' => (new MemberRepository())->allPublic(),
        ], [
            'title' => 'About — NewJeans',
            'description' => 'About NewJeans — the story, milestones and members behind the group.',
        ], 'about', ['about.css']);
    }
}
