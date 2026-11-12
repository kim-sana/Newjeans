<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\AlbumRepository;
use App\Models\SongRepository;
use Throwable;

final class SongController
{
    use AdminFormTrait;

    private AlbumRepository $albums;

    private SongRepository $songs;

    public function __construct()
    {
        $this->albums = new AlbumRepository();
        $this->songs = new SongRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        return $this->renderAdmin('admin/songs/index', [
            'title' => 'Songs',
            'album' => $album,
            'songs' => $this->songs->forAlbum((int) $album['id']),
        ], 'albums');
    }

    public function create(Request $request, array $params = []): Response
    {
        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        return $this->renderForm($album, $this->defaultModel());
    }

    public function store(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        $albumId = (int) $album['id'];
        $input = $this->input($request);
        $errors = $this->validateInput($input);

        if ($errors !== []) {
            $this->backWithErrors($input, $errors, $this->basePath($albumId) . '/create');
        }

        try {
            $this->songs->insert($this->toData($albumId, $input));
        } catch (Throwable $exception) {
            error_log('Song create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the song. Please try again.');

            return Response::redirect(url($this->basePath($albumId)), 303);
        }

        clear_form_state();
        flash('success', 'Song created.');

        return Response::redirect(url($this->basePath($albumId)), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        $song = $this->songs->findForAlbum((int) $album['id'], (int) ($params['id'] ?? 0));

        if ($song === null) {
            return $this->notFound('Song not found.');
        }

        return $this->renderForm($album, $song);
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

        $albumId = (int) $album['id'];
        $song = $this->songs->findForAlbum($albumId, (int) ($params['id'] ?? 0));

        if ($song === null) {
            return $this->notFound('Song not found.');
        }

        $input = $this->input($request);
        $errors = $this->validateInput($input);

        if ($errors !== []) {
            $this->backWithErrors($input, $errors, $this->basePath($albumId) . '/' . (int) $song['id'] . '/edit');
        }

        try {
            $this->songs->update((int) $song['id'], $this->toData($albumId, $input));
        } catch (Throwable $exception) {
            error_log('Song update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the song. Please try again.');

            return Response::redirect(url($this->basePath($albumId)), 303);
        }

        clear_form_state();
        flash('success', 'Song updated.');

        return Response::redirect(url($this->basePath($albumId)), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $album = $this->findAlbum($params);

        if ($album === null) {
            return $this->notFound('Album not found.');
        }

        $albumId = (int) $album['id'];
        $song = $this->songs->findForAlbum($albumId, (int) ($params['id'] ?? 0));

        if ($song === null) {
            flash('error', 'Song not found.');

            return Response::redirect(url($this->basePath($albumId)), 303);
        }

        $this->songs->softDelete((int) $song['id']);
        flash('success', 'Song deleted.');

        return Response::redirect(url($this->basePath($albumId)), 303);
    }

    private function renderForm(array $album, array $model): Response
    {
        return $this->renderAdmin('admin/songs/form', [
            'title' => empty($model['id']) ? 'New Song' : 'Edit Song',
            'album' => $album,
            'model' => $model,
        ], 'albums');
    }

    private function findAlbum(array $params): ?array
    {
        return $this->albums->findWithType((int) ($params['albumId'] ?? 0));
    }

    private function basePath(int $albumId): string
    {
        return '/admin/albums/' . $albumId . '/songs';
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'title' => '',
            'track_number' => '0',
            'is_title' => 0,
            'is_featured' => 0,
            'audio_url' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request): array
    {
        return [
            'title' => trim((string) $request->post('title', '')),
            'track_number' => trim((string) $request->post('track_number', '')),
            'is_title' => in_array((string) $request->post('is_title', ''), ['1', 'on', 'true'], true) ? 1 : 0,
            'is_featured' => in_array((string) $request->post('is_featured', ''), ['1', 'on', 'true'], true) ? 1 : 0,
            'audio_url' => trim((string) $request->post('audio_url', '')),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        $errors = Validator::make($input, [
            'title' => 'required|max:160',
            'track_number' => 'required|integer|min:0',
            'audio_url' => 'nullable|max:255',
        ]);

        if (!isset($errors['audio_url']) && str_contains((string) $input['audio_url'], '..')) {
            $errors['audio_url'] = 'Audio path may not contain "..".';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(int $albumId, array $input): array
    {
        return [
            'album_id' => $albumId,
            'title' => (string) $input['title'],
            'track_number' => (int) $input['track_number'],
            'is_title' => (int) $input['is_title'],
            'is_featured' => (int) $input['is_featured'],
            'audio_url' => $input['audio_url'] !== '' ? (string) $input['audio_url'] : null,
        ];
    }
}
