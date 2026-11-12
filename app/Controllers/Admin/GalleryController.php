<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\GalleryRepository;
use Throwable;

final class GalleryController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/gallery';

    private const UPLOAD_DIR = 'assets/images/uploads/gallery';

    private GalleryRepository $gallery;

    public function __construct()
    {
        $this->gallery = new GalleryRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/gallery/index', [
            'title' => 'Gallery',
            'items' => $this->gallery->allForAdmin(),
        ], 'gallery');
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

        $upload = $this->resolveImage('image', self::UPLOAD_DIR, false, '', $errors, true);
        $input['image'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['image'] = '';
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $data = $this->toData($input);
            $data['created_by'] = $this->currentUserId();
            $this->gallery->insert($data);
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Gallery create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the gallery item. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Gallery item created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $item = $this->findItem($params);

        if ($item === null) {
            return $this->notFound('Gallery item not found.');
        }

        return $this->renderForm($item);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $item = $this->findItem($params);

        if ($item === null) {
            return $this->notFound('Gallery item not found.');
        }

        $id = (int) $item['id'];
        $currentImage = (string) ($item['image'] ?? '');
        $input = $this->input($request, $item);
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('image', self::UPLOAD_DIR, true, $currentImage, $errors, true);
        $input['image'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['image'] = $currentImage;
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->gallery->update($id, $this->toData($input, $item));
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Gallery update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the gallery item. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Gallery item updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $item = $this->findItem($params);

        if ($item === null) {
            flash('error', 'Gallery item not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->gallery->softDelete((int) $item['id']);
        flash('success', 'Gallery item deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/gallery/form', [
            'title' => empty($model['id']) ? 'New Gallery Item' : 'Edit Gallery Item',
            'model' => $model,
        ], 'gallery');
    }

    private function findItem(array $params): ?array
    {
        return $this->gallery->find((int) ($params['id'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'image' => '',
            'alt_text' => '',
            'caption' => '',
            'sort_order' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request, array $model): array
    {
        return [
            'image' => (string) ($model['image'] ?? ''),
            'alt_text' => trim((string) $request->post('alt_text', '')),
            'caption' => trim((string) $request->post('caption', '')),
            'sort_order' => trim((string) $request->post('sort_order', '')),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        return Validator::make($input, [
            'alt_text' => 'nullable|max:200',
            'caption' => 'nullable|max:200',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $model
     * @return array<string, mixed>
     */
    private function toData(array $input, ?array $model = null): array
    {
        if ($input['sort_order'] === '') {
            $sortOrder = $model !== null
                ? (int) ($model['sort_order'] ?? 0)
                : $this->gallery->nextSortOrder();
        } else {
            $sortOrder = (int) $input['sort_order'];
        }

        return [
            'image' => (string) $input['image'],
            'alt_text' => $input['alt_text'] !== '' ? (string) $input['alt_text'] : null,
            'caption' => $input['caption'] !== '' ? (string) $input['caption'] : null,
            'sort_order' => $sortOrder,
        ];
    }

    private function currentUserId(): ?int
    {
        $user = Auth::user();
        $id = is_array($user) ? (int) ($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }
}
