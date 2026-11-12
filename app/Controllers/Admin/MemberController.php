<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AdminFormTrait;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\MemberRepository;
use Throwable;

final class MemberController
{
    use AdminFormTrait;

    private const BASE_PATH = '/admin/members';

    private const UPLOAD_DIR = 'assets/images/uploads/members';

    private MemberRepository $members;

    public function __construct()
    {
        $this->members = new MemberRepository();
    }

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderAdmin('admin/members/index', [
            'title' => 'Members',
            'members' => $this->members->allForAdmin(),
        ], 'members');
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

        $upload = $this->resolveImage('photo', self::UPLOAD_DIR, false, '', $errors, true);
        $input['photo'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['photo'] = '';
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/create');
        }

        try {
            $data = $this->toData($input);
            $data['created_by'] = $this->currentUserId();
            $this->members->insert($data);
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Member create failed: ' . $exception->getMessage());
            flash('error', 'Unable to create the member. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Member created.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function edit(Request $request, array $params = []): Response
    {
        $member = $this->findMember($params);

        if ($member === null) {
            return $this->notFound('Member not found.');
        }

        return $this->renderForm($member);
    }

    public function update(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $member = $this->findMember($params);

        if ($member === null) {
            return $this->notFound('Member not found.');
        }

        $id = (int) $member['id'];
        $currentPhoto = (string) ($member['photo'] ?? '');
        $input = $this->input($request, $member);
        $errors = $this->validateInput($input);

        $upload = $this->resolveImage('photo', self::UPLOAD_DIR, true, $currentPhoto, $errors, true);
        $input['photo'] = $upload['path'];

        if ($errors !== []) {
            $this->removeUpload($upload['new_upload']);
            $input['photo'] = $currentPhoto;
            $this->backWithErrors($input, $errors, self::BASE_PATH . '/' . $id . '/edit');
        }

        try {
            $this->members->update($id, $this->toData($input));
        } catch (Throwable $exception) {
            $this->removeUpload($upload['new_upload']);
            error_log('Member update failed: ' . $exception->getMessage());
            flash('error', 'Unable to update the member. Please try again.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        clear_form_state();
        flash('success', 'Member updated.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        if (!$this->csrfValid($request)) {
            return $this->csrfFailure();
        }

        $member = $this->findMember($params);

        if ($member === null) {
            flash('error', 'Member not found.');

            return Response::redirect(url(self::BASE_PATH), 303);
        }

        $this->members->softDelete((int) $member['id']);
        flash('success', 'Member deleted.');

        return Response::redirect(url(self::BASE_PATH), 303);
    }

    private function renderForm(array $model): Response
    {
        return $this->renderAdmin('admin/members/form', [
            'title' => empty($model['id']) ? 'New Member' : 'Edit Member',
            'model' => $model,
        ], 'members');
    }

    private function findMember(array $params): ?array
    {
        return $this->members->find((int) ($params['id'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultModel(): array
    {
        return [
            'id' => null,
            'name' => '',
            'photo' => '',
            'member_roles' => '',
            'description' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request, array $model): array
    {
        return [
            'name' => trim((string) $request->post('name', '')),
            'photo' => (string) ($model['photo'] ?? ''),
            'member_roles' => trim((string) $request->post('member_roles', '')),
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
            'name' => 'required|max:120',
            'member_roles' => 'required|max:120',
            'description' => 'required|max:255',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toData(array $input): array
    {
        return [
            'name' => (string) $input['name'],
            'photo' => (string) $input['photo'],
            'member_roles' => (string) $input['member_roles'],
            'description' => (string) $input['description'],
        ];
    }

    private function currentUserId(): ?int
    {
        $user = Auth::user();
        $id = is_array($user) ? (int) ($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }
}
