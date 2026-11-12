<?php

declare(strict_types=1);

use App\Controllers\Admin\AlbumController;
use App\Controllers\Admin\AlbumTypeController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\EventController;
use App\Controllers\Admin\GalleryController;
use App\Controllers\Admin\HistoryController;
use App\Controllers\Admin\MemberController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\SongController;
use App\Controllers\Public\AboutController;
use App\Controllers\Public\DiscographyController;
use App\Controllers\Public\GalleryController as PublicGalleryController;
use App\Controllers\Public\HomeController;
use App\Controllers\Public\NewsController as PublicNewsController;
use App\Controllers\Public\ScheduleController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use Dotenv\Dotenv;

$root = dirname(__DIR__);

if (PHP_SAPI === 'cli-server') {
    $requested = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $requested = is_string($requested) && $requested !== '' ? $requested : '/';

    if ($requested !== '/' && is_file(__DIR__ . $requested)) {
        return false;
    }
}

require $root . '/vendor/autoload.php';

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$required = ['APP_URL', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'];
$missing = array_values(array_filter(
    $required,
    static fn (string $key): bool => env($key) === null || env($key) === ''
));

if ($missing !== []) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Missing required environment variables: ' . implode(', ', $missing) . PHP_EOL;
    echo 'Copy .env.example to .env and fill in the values.' . PHP_EOL;
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');

session_start_safe();

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/discography', [DiscographyController::class, 'index']);
$router->get('/discography/{id}', [DiscographyController::class, 'show']);
$router->get('/schedule', [ScheduleController::class, 'index']);
$router->get('/news', [PublicNewsController::class, 'index']);
$router->get('/news/{slug}', [PublicNewsController::class, 'show']);
$router->get('/about', [AboutController::class, 'index']);
$router->get('/gallery', [PublicGalleryController::class, 'index']);

$router->get('/index.html', static fn (): Response => Response::redirect(url('/'), 301));
$router->get('/home-legacy.html', static fn (): Response => Response::redirect(url('/'), 301));
$router->get('/pages/discography.html', static fn (): Response => Response::redirect(url('/discography'), 301));
$router->get('/pages/schedule.html', static fn (): Response => Response::redirect(url('/schedule'), 301));
$router->get('/pages/about.html', static fn (): Response => Response::redirect(url('/about'), 301));
$router->get('/pages/gallery.html', static fn (): Response => Response::redirect(url('/gallery'), 301));
$router->get('/pages/news.html', static function (Request $request): Response {
    $slug = $request->query('slug');

    if (is_string($slug) && trim($slug) !== '') {
        return Response::redirect(url('/news/' . rawurlencode(trim($slug))), 301);
    }

    return Response::redirect(url('/news'), 301);
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/album-types', [AlbumTypeController::class, 'index']);
$router->get('/admin/album-types/create', [AlbumTypeController::class, 'create']);
$router->post('/admin/album-types', [AlbumTypeController::class, 'store']);
$router->get('/admin/album-types/{id}/edit', [AlbumTypeController::class, 'edit']);
$router->post('/admin/album-types/{id}', [AlbumTypeController::class, 'update']);
$router->post('/admin/album-types/{id}/delete', [AlbumTypeController::class, 'destroy']);

$router->get('/admin/albums', [AlbumController::class, 'index']);
$router->get('/admin/albums/create', [AlbumController::class, 'create']);
$router->post('/admin/albums', [AlbumController::class, 'store']);
$router->get('/admin/albums/{id}/edit', [AlbumController::class, 'edit']);
$router->post('/admin/albums/{id}', [AlbumController::class, 'update']);
$router->post('/admin/albums/{id}/delete', [AlbumController::class, 'destroy']);

$router->get('/admin/albums/{albumId}/songs', [SongController::class, 'index']);
$router->get('/admin/albums/{albumId}/songs/create', [SongController::class, 'create']);
$router->post('/admin/albums/{albumId}/songs', [SongController::class, 'store']);
$router->get('/admin/albums/{albumId}/songs/{id}/edit', [SongController::class, 'edit']);
$router->post('/admin/albums/{albumId}/songs/{id}', [SongController::class, 'update']);
$router->post('/admin/albums/{albumId}/songs/{id}/delete', [SongController::class, 'destroy']);

$router->get('/admin/news', [NewsController::class, 'index']);
$router->get('/admin/news/create', [NewsController::class, 'create']);
$router->post('/admin/news', [NewsController::class, 'store']);
$router->get('/admin/news/{id}/edit', [NewsController::class, 'edit']);
$router->post('/admin/news/{id}', [NewsController::class, 'update']);
$router->post('/admin/news/{id}/delete', [NewsController::class, 'destroy']);

$router->get('/admin/events', [EventController::class, 'index']);
$router->get('/admin/events/create', [EventController::class, 'create']);
$router->post('/admin/events', [EventController::class, 'store']);
$router->get('/admin/events/{id}/edit', [EventController::class, 'edit']);
$router->post('/admin/events/{id}', [EventController::class, 'update']);
$router->post('/admin/events/{id}/delete', [EventController::class, 'destroy']);

$router->get('/admin/members', [MemberController::class, 'index']);
$router->get('/admin/members/create', [MemberController::class, 'create']);
$router->post('/admin/members', [MemberController::class, 'store']);
$router->get('/admin/members/{id}/edit', [MemberController::class, 'edit']);
$router->post('/admin/members/{id}', [MemberController::class, 'update']);
$router->post('/admin/members/{id}/delete', [MemberController::class, 'destroy']);

$router->get('/admin/histories', [HistoryController::class, 'index']);
$router->get('/admin/histories/create', [HistoryController::class, 'create']);
$router->post('/admin/histories', [HistoryController::class, 'store']);
$router->get('/admin/histories/{id}/edit', [HistoryController::class, 'edit']);
$router->post('/admin/histories/{id}', [HistoryController::class, 'update']);
$router->post('/admin/histories/{id}/delete', [HistoryController::class, 'destroy']);

$router->get('/admin/gallery', [GalleryController::class, 'index']);
$router->get('/admin/gallery/create', [GalleryController::class, 'create']);
$router->post('/admin/gallery', [GalleryController::class, 'store']);
$router->get('/admin/gallery/{id}/edit', [GalleryController::class, 'edit']);
$router->post('/admin/gallery/{id}', [GalleryController::class, 'update']);
$router->post('/admin/gallery/{id}/delete', [GalleryController::class, 'destroy']);

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) && $requestPath !== '' ? $requestPath : '/';

if ($requestPath === '/admin' || str_starts_with($requestPath, '/admin/')) {
    Auth::requireAdmin();
}

$router->notFound(static function (): Response {
    $content = View::render('pages/404', [
        'message' => 'The page you are looking for does not exist.',
    ]);

    return Response::html(View::layout('layouts/public', $content, [
        'title' => 'Page not found — NewJeans',
        'description' => 'The requested NewJeans page could not be found.',
        'active' => '',
        'styles' => [],
        'scripts' => [],
        'bodyClass' => '',
    ]), 404);
});

$router
    ->dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), (string) ($_SERVER['REQUEST_URI'] ?? '/'))
    ->send();
