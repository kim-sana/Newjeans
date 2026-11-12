<?php

$errors = form_errors();
$typeModel = is_array($model ?? null) ? $model : [];
$typeId = isset($typeModel['id']) ? (int) $typeModel['id'] : 0;
$isEdit = $typeId > 0;
$nameValue = (string) old('name', $typeModel['name'] ?? '');
$slugValue = (string) old('slug', $typeModel['slug'] ?? '');
$action = $isEdit ? url('/admin/album-types/' . $typeId) : url('/admin/album-types');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit album type' : 'New album type' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <label for="typeName" class="form-label">Name</label>
                <input
                    type="text"
                    id="typeName"
                    name="name"
                    value="<?= e($nameValue) ?>"
                    class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                    maxlength="80"
                    required
                >
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="typeSlug" class="form-label">Slug</label>
                <input
                    type="text"
                    id="typeSlug"
                    name="slug"
                    value="<?= e($slugValue) ?>"
                    class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>"
                    maxlength="80"
                    placeholder="mini-album"
                >
                <?php if (isset($errors['slug'])): ?>
                    <div class="invalid-feedback"><?= e($errors['slug']) ?></div>
                <?php endif; ?>
                <div class="form-text">Leave blank to generate from the name. Slugs are kept unique automatically.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create type' ?></button>
                <a href="<?= e(url('/admin/album-types')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
