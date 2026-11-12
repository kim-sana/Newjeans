<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\EventRepository;
use App\Support\Slug;
use Throwable;

final class EventController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/events';

    private const UPLOAD_DIR = 'assets/images/uploads/events';

    private EventRepository $events;

    public function __construct()
    {
        $this->events = new EventRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/events/index', [
            'title' => 'Events',
            'events' => $this->events->allForAdmin(),
        ], 'events');
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

        $upload = $this->resolveImage('poster', self::UPLOAD_DIR, false, '', $errors, false);
        $input['poster'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['poster'] = '';
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $data = $this->toData($input);
            $data['created_by'] = $this->currentUserId();
            $this->events->insert($data);
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Event create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the event. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Event created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $event = $this->findEvent($params);

        if ($event === null) {
            return $this->notFound('Event not found.');
        }

        return $this->renderForm($event);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $event = $this->findEvent($params);

        if ($event === null) {
            return $this->notFound('Event not found.');
        }

        $id = (int) $event['id'];
        $currentPoster = (string) ($event['poster'] ?? '');
        $input = $this->input($request, $event);
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('poster', self::UPLOAD_DIR, true, $currentPoster, $errors, false);
        $input['poster'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['poster'] = $currentPoster;
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->events->update($id, $this->toData($input, $id));
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Event update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the event. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Event updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $event = $this->findEvent($params);

        if ($event === null) {
            flash('error', 'Event not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->events->softDelete((int) $event['id']);
        flash('success', 'Event deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/events/form', [
            'title' => empty($model['id']) ? 'New Event' : 'Edit Event',
            'model' => $model,
        ], 'events');
    }

    private function findEvent(array $params): ?array
    {
        return $this->events->find((int) ($params['id'] ?? 0));
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
            'venue' => '',
            'city' => '',
            'event_date' => '',
            'poster' => '',
            'description' => '',
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
            'venue' => trim((string) $request->post('venue', '')),
            'city' => trim((string) $request->post('city', '')),
            'event_date' => trim((string) $request->post('event_date', '')),
            'poster' => (string) ($model['poster'] ?? ''),
            'description' => trim((string) $request->post('description', '')),
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
            'venue' => 'required|max:160',
            'city' => 'required|max:120',
            'event_date' => 'nullable|date',
            'description' => 'required',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(array $input, ?int $ignoreId = null): array
    {
        return [
            'slug' => Slug::unique('events', $this->slugSource($input), $ignoreId, 190),
            'title' => (string) $input['title'],
            'venue' => (string) $input['venue'],
            'city' => (string) $input['city'],
            'event_date' => $this->normalizeDateTime((string) $input['event_date']),
            'poster' => $input['poster'] !== '' ? (string) $input['poster'] : null,
            'description' => (string) $input['description'],
        ];
    }

    private function slugSource(array $input): string
    {
        return $input['slug'] !== '' ? (string) $input['slug'] : (string) $input['title'];
    }

    private function currentUserId(): ?int
    {
        $user = Auth::user();
        $id = is_array($user) ? (int) ($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }
}
