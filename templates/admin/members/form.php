<?php

$errors = form_errors();
$memberModel = is_array($model ?? null) ? $model : [];
$memberId = isset($memberModel['id']) ? (int) $memberModel['id'] : 0;
$isEdit = $memberId > 0;

$nameValue = (string) old('name', $memberModel['name'] ?? '');
$rolesValue = (string) old('member_roles', $memberModel['member_roles'] ?? '');
$descriptionValue = (string) old('description', $memberModel['description'] ?? '');
$photoValue = (string) old('photo', $memberModel['photo'] ?? '');

$action = $isEdit ? url('/admin/members/' . $memberId) : url('/admin/members');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit member' : 'New member' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="memberName" class="form-label">Name</label>
                    <input
                        type="text"
                        id="memberName"
                        name="name"
                        value="<?= e($nameValue) ?>"
                        class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                        maxlength="120"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="memberRoles" class="form-label">Roles</label>
                    <input
                        type="text"
                        id="memberRoles"
                        name="member_roles"
                        value="<?= e($rolesValue) ?>"
                        class="form-control<?= isset($errors['member_roles']) ? ' is-invalid' : '' ?>"
                        maxlength="120"
                        placeholder="Leader &middot; Vocalist"
                        required
                    >
                    <?php if (isset($errors['member_roles'])): ?>
                        <div class="invalid-feedback"><?= e($errors['member_roles']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <?= \App\Core\View::render('admin/partials/image-field', [
                        'name' => 'photo',
                        'label' => 'Photo',
                        'current' => $photoValue,
                        'required' => !$isEdit,
                        'error' => $errors['photo'] ?? null,
                        'help' => 'JPEG, PNG, WebP, or GIF up to 5 MB.' . ($isEdit ? ' Leave empty to keep the current photo.' : ''),
                    ]) ?>
                </div>

                <div class="col-12">
                    <label for="memberDescription" class="form-label">Description</label>
                    <textarea
                        id="memberDescription"
                        name="description"
                        rows="3"
                        class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>"
                        maxlength="255"
                        required
                    ><?= e($descriptionValue) ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create member' ?></button>
                <a href="<?= e(url('/admin/members')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
