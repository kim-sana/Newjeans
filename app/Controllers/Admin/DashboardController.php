<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class DashboardController
{
    public function index(Request $request, array $params = []): Response
    {
        $pdo = Database::pdo();

        $definitions = [
            ['table' => 'album_types', 'label' => 'Album Types', 'icon' => 'bi-tags', 'theme' => 'primary'],
            ['table' => 'albums', 'label' => 'Albums', 'icon' => 'bi-vinyl', 'theme' => 'info'],
            ['table' => 'songs', 'label' => 'Songs', 'icon' => 'bi-music-note-beamed', 'theme' => 'success'],
            ['table' => 'news', 'label' => 'News', 'icon' => 'bi-newspaper', 'theme' => 'warning'],
            ['table' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event', 'theme' => 'danger'],
            ['table' => 'members', 'label' => 'Members', 'icon' => 'bi-people', 'theme' => 'primary'],
            ['table' => 'histories', 'label' => 'Histories', 'icon' => 'bi-clock-history', 'theme' => 'info'],
            ['table' => 'gallery_items', 'label' => 'Gallery Items', 'icon' => 'bi-images', 'theme' => 'success'],
        ];

        $counts = [];

        foreach ($definitions as $definition) {
            $counts[] = $definition + [
                'count' => (int) $pdo->query(
                    'SELECT COUNT(*) FROM `' . $definition['table'] . '` WHERE deleted_at IS NULL'
                )->fetchColumn(),
            ];
        }

        try {
            $totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
            $pendingOrders = (int) $pdo->query(
                "SELECT COUNT(*) FROM orders WHERE status = 'PENDING_PAYMENT'"
            )->fetchColumn();
        } catch (\PDOException) {
            $totalOrders = 0;
            $pendingOrders = 0;
        }

        $counts[] = [
            'table' => 'orders',
            'label' => 'Orders',
            'icon' => 'bi-receipt',
            'theme' => 'warning',
            'count' => $totalOrders,
        ];
        $counts[] = [
            'table' => 'orders',
            'label' => 'Pending Orders',
            'icon' => 'bi-hourglass-split',
            'theme' => 'danger',
            'count' => $pendingOrders,
        ];

        $publishedNews = (int) $pdo->query(
            "SELECT COUNT(*) FROM news WHERE status = 'published' AND deleted_at IS NULL"
        )->fetchColumn();

        $featuredSongs = (int) $pdo->query(
            'SELECT COUNT(*) FROM songs WHERE is_featured = 1 AND deleted_at IS NULL'
        )->fetchColumn();

        $content = View::render('admin/dashboard', [
            'counts' => $counts,
            'publishedNews' => $publishedNews,
            'featuredSongs' => $featuredSongs,
        ]);

        return Response::html(View::layout('layouts/admin', $content, [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'active' => 'dashboard',
        ]));
    }
}
