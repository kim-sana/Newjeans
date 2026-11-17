<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\NewsRepository;
use App\Support\Slug;
use Throwable;

final class NewsController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/news';

    private const UPLOAD_DIR = 'assets/images/uploads/news';

    private NewsRepository $news;

    public function __construct()
    {
        $this->news = new NewsRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/news/index', [
            'title' => 'News',
            'newsItems' => $this->news->allForAdmin(),
        ], 'news');
    }

    public function create(Request $request, array $params = []): Response
    {
        return $this->renderForm($this->defaultModel());
    }

    public function store(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $input = $this->input($request, $this->defaultModel());
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('thumbnail', self::UPLOAD_DIR, false, '', $errors);
        $input['thumbnail'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['thumbnail'] = '';
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $data = $this->toData($input);
            $data['created_by'] = $this->currentUserId();
            $this->news->insert($data);
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('News create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the news item. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'News item created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $newsItem = $this->findNews($params);

        if ($newsItem === null) {
            return $this->notFound('News item not found.');
        }

        return $this->renderForm($newsItem);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $newsItem = $this->findNews($params);

        if ($newsItem === null) {
            return $this->notFound('News item not found.');
        }

        $id = (int) $newsItem['id'];
        $currentThumbnail = (string) ($newsItem['thumbnail'] ?? '');
        $input = $this->input($request, $newsItem);
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('thumbnail', self::UPLOAD_DIR, true, $currentThumbnail, $errors);
        $input['thumbnail'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['thumbnail'] = $currentThumbnail;
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->news->update($id, $this->toData($input, $id));
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('News update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the news item. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'News item updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $newsItem = $this->findNews($params);

        if ($newsItem === null) {
            flash('error', 'News item not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->news->softDelete((int) $newsItem['id']);
        flash('success', 'News item deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/news/form', [
            'title' => empty($model['id']) ? 'New News Item' : 'Edit News Item',
            'model' => $model,
        ], 'news');
    }

    private function findNews(array $params): ?array
    {
        return $this->news->find((int) ($params['id'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'slug' => '',
            'title' => '',
            'thumbnail' => '',
            'content' => '',
            'status' => 'draft',
            'published_at' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request, array $model): array
    {
        return [
            'slug' => trim((string) $request->post('slug', '')),
            'title' => trim((string) $request->post('title', '')),
            'thumbnail' => (string) ($model['thumbnail'] ?? ''),
            'content' => trim((string) $request->post('content', '')),
            'status' => trim((string) $request->post('status', 'draft')),
            'published_at' => trim((string) $request->post('published_at', '')),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        return Validator::make($input, [
            'slug' => 'nullable|max:190',
            'title' => 'required|max:200',
            'content' => 'required',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(array $input, ?int $ignoreId = null): array
    {
        return [
            'slug' => Slug::unique('news', $this->slugSource($input), $ignoreId, 190),
            'title' => (string) $input['title'],
            'thumbnail' => (string) $input['thumbnail'],
            'content' => (string) $input['content'],
            'status' => (string) $input['status'],
            'published_at' => $this->resolvePublishedAt($input),
            'source_name' => null,
            'source_url' => null,
        ];
    }

    private function slugSource(array $input): string
    {
        return $input['slug'] !== '' ? (string) $input['slug'] : (string) $input['title'];
    }

    private function resolvePublishedAt(array $input): ?string
    {
        $publishedAt = $this->normalizeDateTime((string) $input['published_at']);

        if ($publishedAt !== null) {
            return $publishedAt;
        }

        return (string) $input['status'] === 'published' ? date('Y-m-d H:i:s') : null;
    }

    private function currentUserId(): ?int
    {
        $user = Auth::user();
        $id = is_array($user) ? (int) ($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }
}
