<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\AlbumTypeRepository;
use App\Support\Slug;
use Throwable;

final class AlbumTypeController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/album-types';

    private AlbumTypeRepository $types;

    public function __construct()
    {
        $this->types = new AlbumTypeRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/album-types/index', [
            'title' => 'Album Types',
            'types' => $this->types->allWithAlbumCounts(),
        ], 'album-types');
    }

    public function create(Request $request, array $params = []): Response
    {
        return $this->renderForm([
            'id' => null,
            'name' => '',
        ]);
    }

    public function store(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $input = $this->input($request);
        $errors = $this->validateInput($input);

        if ($errors !== []) {
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $this->types->insert([
                'name' => $input['name'],
                'slug' => Slug::unique('album_types', $input['name'], null, 80),
            ]);
        } catch (Throwable $exception) {
            error_log('Album type create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the album type. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Album type created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $type = $this->findType($params);

        if ($type === null) {
            return $this->notFound('Album type not found.');
        }

        return $this->renderForm($type);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $type = $this->findType($params);

        if ($type === null) {
            return $this->notFound('Album type not found.');
        }

        $id = (int) $type['id'];
        $input = $this->input($request);
        $errors = $this->validateInput($input, $id);

        if ($errors !== []) {
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->types->update($id, [
                'name' => $input['name'],
                'slug' => Slug::unique('album_types', $input['name'], $id, 80),
            ]);
        } catch (Throwable $exception) {
            error_log('Album type update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the album type. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Album type updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $type = $this->findType($params);

        if ($type === null) {
            flash('error', 'Album type not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $id = (int) $type['id'];
        $albumCount = $this->types->countActiveAlbums($id);

        if ($albumCount > 0) {
            flash('error', sprintf(
                'This album type still has %d active album%s. Delete or move those albums first.',
                $albumCount,
                $albumCount === 1 ? '' : 's'
            ));

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->types->softDelete($id);
        flash('success', 'Album type deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/album-types/form', [
            'title' => empty($model['id']) ? 'New Album Type' : 'Edit Album Type',
            'model' => $model,
        ], 'album-types');
    }

    private function findType(array $params): ?array
    {
        return $this->types->find((int) ($params['id'] ?? 0));
    }

    /**
     * @return array{name: string}
     */
    private function input(Request $request): array
    {
        return [
            'name' => trim((string) $request->post('name', '')),
        ];
    }

    /**
     * @param array{name: string} $input
     * @return array<string, string>
     */
    private function validateInput(array $input, ?int $ignoreId = null): array
    {
        $errors = Validator::make($input, [
            'name' => 'required|max:80',
        ]);

        if (!isset($errors['name']) && $this->types->existsByName($input['name'], $ignoreId)) {
            $errors['name'] = 'Name is already in use.';
        }

        return $errors;
    }
}
