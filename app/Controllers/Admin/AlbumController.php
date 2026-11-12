<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\AlbumRepository;
use App\Models\AlbumTypeRepository;
use RuntimeException;
use Throwable;

final class AlbumController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/albums';

    private AlbumRepository $albums;

    private AlbumTypeRepository $types;

    public function __construct()
    {
        $this->albums = new AlbumRepository();
        $this->types = new AlbumTypeRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/albums/index', [
            'title' => 'Albums',
            'albums' => $this->albums->allWithTypeAndSongCount(),
        ], 'albums');
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

        $file = $_FILES['cover_album'] ?? null;
        $hasUpload = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $newUpload = null;

        if ($hasUpload) {
            try {
                $newUpload = Upload::store($file, 'assets/images/uploads/albums');
                $input['cover_album'] = $newUpload;
            } catch (RuntimeException $exception) {
                $errors['cover_album'] = $exception->getMessage();
            }
        } else {
            $errors['cover_album'] = 'Cover image is required.';
        }

        if ($errors !== []) {
            if ($newUpload !== null) {
                @unlink(base_path('public/' . $newUpload));
            }

            $input['cover_album'] = '';
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $this->albums->insert($this->toData($input));
        } catch (Throwable $exception) {
            if ($newUpload !== null) {
                @unlink(base_path('public/' . $newUpload));
            }

            error_log('Album create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the album. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Album created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        return $this->renderForm($album);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        $id = (int) $album['id'];
        $input = $this->input($request, $album);
        $errors = $this->validateInput($input);

        $file = $_FILES['cover_album'] ?? null;
        $hasUpload = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $newUpload = null;

        if ($hasUpload) {
            try {
                $newUpload = Upload::store($file, 'assets/images/uploads/albums');
                $input['cover_album'] = $newUpload;
            } catch (RuntimeException $exception) {
                $errors['cover_album'] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            if ($newUpload !== null) {
                @unlink(base_path('public/' . $newUpload));
            }

            $input['cover_album'] = (string) $album['cover_album'];
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->albums->update($id, $this->toData($input));
        } catch (Throwable $exception) {
            if ($newUpload !== null) {
                @unlink(base_path('public/' . $newUpload));
            }

            error_log('Album update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the album. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Album updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $album = $this->findAlbum($params);

        if ($album === null) {
            flash('error', 'Album not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $id = (int) $album['id'];
        $songCount = $this->albums->countActiveSongs($id);

        if ($songCount > 0) {
            flash('error', sprintf(
                'This album still has %d active song%s. Delete those songs first.',
                $songCount,
                $songCount === 1 ? '' : 's'
            ));

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->albums->softDelete($id);
        flash('success', 'Album deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/albums/form', [
            'title' => empty($model['id']) ? 'New Album' : 'Edit Album',
            'model' => $model,
            'types' => $this->types->all('name ASC'),
        ], 'albums');
    }

    private function findAlbum(array $params): ?array
    {
        return $this->albums->findWithType((int) ($params['id'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'album_type_id' => '',
            'name' => '',
            'cover_album' => '',
            'release_date' => '',
            'spotify_url' => '',
            'youtube_url' => '',
            'price' => '0.00',
            'stock' => '0',
            'is_for_sale' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request, array $model): array
    {
        $forSale = (string) $request->post('is_for_sale', '');

        return [
            'album_type_id' => trim((string) $request->post('album_type_id', '')),
            'name' => trim((string) $request->post('name', '')),
            'cover_album' => (string) ($model['cover_album'] ?? ''),
            'release_date' => trim((string) $request->post('release_date', '')),
            'spotify_url' => trim((string) $request->post('spotify_url', '')),
            'youtube_url' => trim((string) $request->post('youtube_url', '')),
            'price' => trim((string) $request->post('price', '')),
            'stock' => trim((string) $request->post('stock', '')),
            'is_for_sale' => in_array($forSale, ['1', 'on', 'true'], true) ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        $errors = Validator::make($input, [
            'album_type_id' => 'required|integer|min:1',
            'name' => 'required|max:160',
            'release_date' => 'nullable|date',
            'spotify_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        if (!isset($errors['album_type_id']) && $this->types->find((int) $input['album_type_id']) === null) {
            $errors['album_type_id'] = 'Select a valid album type.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(array $input): array
    {
        return [
            'album_type_id' => (int) $input['album_type_id'],
            'name' => (string) $input['name'],
            'cover_album' => (string) $input['cover_album'],
            'release_date' => $this->normalizeDate((string) $input['release_date']),
            'spotify_url' => $input['spotify_url'] !== '' ? (string) $input['spotify_url'] : null,
            'youtube_url' => $input['youtube_url'] !== '' ? (string) $input['youtube_url'] : null,
            'price' => (string) $input['price'],
            'stock' => (int) $input['stock'],
            'is_for_sale' => (int) $input['is_for_sale'],
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
