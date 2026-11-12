<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\NewsRepository;

final class NewsController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        $slug = $request->query('slug');

        if (is_string($slug) && trim($slug) !== '') {
            return Response::redirect(url('/news/' . rawurlencode(trim($slug))), 301);
        }

        $stories = (new NewsRepository())->published();
        $featured = $stories[0] ?? null;
        $rest = array_slice($stories, 1);

        return $this->renderPage('pages/news-list', [
            'stories' => $stories,
            'featured' => $featured,
            'rest' => $rest,
        ], [
            'title' => 'News — NewJeans',
            'description' => 'Verified NewJeans milestones, chart history, and stories from the Bunnies universe.',
        ], 'news', ['news.css']);
    }

    public function show(Request $request, array $params = []): Response
    {
        $slug = (string) ($params['slug'] ?? '');
        $repository = new NewsRepository();
        $story = $repository->findPublishedBySlug($slug);

        if ($story === null) {
            return $this->renderPage('pages/404', [
                'message' => "This story isn't available. It may have been removed or unpublished.",
            ], [
                'title' => 'Story not found — NewJeans',
                'description' => 'The requested NewJeans story could not be found.',
                'status' => 404,
            ], 'news', ['news.css']);
        }

        $title = (string) ($story['title'] ?? 'News story');
        $content = (string) ($story['content'] ?? '');

        return $this->renderPage('pages/news-article', [
            'story' => $story,
            'related' => $repository->relatedPublished($slug, 3),
        ], [
            'title' => $title . ' — NewJeans',
            'description' => excerpt($content, 155),
        ], 'news', ['news.css']);
    }
}
