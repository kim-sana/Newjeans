<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\HistoryRepository;
use Throwable;

final class HistoryController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/histories';

    private const UPLOAD_DIR = 'assets/images/uploads/histories';

    private HistoryRepository $histories;

    public function __construct()
    {
        $this->histories = new HistoryRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/histories/index', [
            'title' => 'Histories',
            'histories' => $this->histories->allForAdmin(),
        ], 'histories');
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
            $this->histories->insert($data);
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('History create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the history entry. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'History entry created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $history = $this->findHistory($params);

        if ($history === null) {
            return $this->notFound('History entry not found.');
        }

        return $this->renderForm($history);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $history = $this->findHistory($params);

        if ($history === null) {
            return $this->notFound('History entry not found.');
        }

        $id = (int) $history['id'];
        $currentImage = (string) ($history['image'] ?? '');
        $input = $this->input($request, $history);
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('image', self::UPLOAD_DIR, true, $currentImage, $errors, true);
        $input['image'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['image'] = $currentImage;
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->histories->update($id, $this->toData($input));
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('History update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the history entry. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'History entry updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $history = $this->findHistory($params);

        if ($history === null) {
            flash('error', 'History entry not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->histories->softDelete((int) $history['id']);
        flash('success', 'History entry deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/histories/form', [
            'title' => empty($model['id']) ? 'New History' : 'Edit History',
            'model' => $model,
        ], 'histories');
    }

    private function findHistory(array $params): ?array
    {
        return $this->histories->find((int) ($params['id'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'image' => '',
            'title' => '',
            'description' => '',
            'event_date' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request, array $model): array
    {
        return [
            'image' => (string) ($model['image'] ?? ''),
            'title' => trim((string) $request->post('title', '')),
            'description' => trim((string) $request->post('description', '')),
            'event_date' => trim((string) $request->post('event_date', '')),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        return Validator::make($input, [
            'title' => 'required|max:160',
            'description' => 'required|max:255',
            'event_date' => 'nullable|date',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(array $input): array
    {
        return [
            'image' => (string) $input['image'],
            'title' => (string) $input['title'],
            'description' => (string) $input['description'],
            'event_date' => $this->normalizeDateTime((string) $input['event_date']),
        ];
    }

    private function currentUserId(): ?int
    {
        $user = Auth::user();
        $id = is_array($user) ? (int) ($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }
}
