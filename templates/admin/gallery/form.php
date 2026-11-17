<?php

$errors = form_errors();
$itemModel = is_array($model ?? null) ? $model : [];
$itemId = isset($itemModel['id']) ? (int) $itemModel['id'] : 0;
$isEdit = $itemId > 0;

$imageValue = (string) old('image', $itemModel['image'] ?? '');
$altTextValue = (string) old('alt_text', $itemModel['alt_text'] ?? '');
$sortOrderValue = (string) old('sort_order', $itemModel['sort_order'] ?? '');

$action = $isEdit ? url('/admin/gallery/' . $itemId) : url('/admin/gallery');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit gallery item' : 'New gallery item' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <?= \App\Core\View::render('admin/partials/image-field', [
                        'name' => 'image',
                        'label' => 'Image',
                        'current' => $imageValue,
                        'required' => !$isEdit,
                        'error' => $errors['image'] ?? null,
                        'help' => 'JPEG, PNG, WebP, or GIF up to 5 MB.' . ($isEdit ? ' Leave empty to keep the current image.' : ''),
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <label for="galleryAlt" class="form-label">Alt text</label>
                    <input
                        type="text"
                        id="galleryAlt"
                        name="alt_text"
                        value="<?= e($altTextValue) ?>"
                        class="form-control<?= isset($errors['alt_text']) ? ' is-invalid' : '' ?>"
                        maxlength="200"
                        placeholder="Describe the image for screen readers"
                    >
                    <?php if (isset($errors['alt_text'])): ?>
                        <div class="invalid-feedback"><?= e($errors['alt_text']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="gallerySortOrder" class="form-label">Sort order</label>
                    <input
                        type="number"
                        id="gallerySortOrder"
                        name="sort_order"
                        value="<?= e($sortOrderValue) ?>"
                        class="form-control<?= isset($errors['sort_order']) ? ' is-invalid' : '' ?>"
                        min="0"
                        step="1"
                        placeholder="<?= $isEdit ? '' : 'Auto' ?>"
                    >
                    <?php if (isset($errors['sort_order'])): ?>
                        <div class="invalid-feedback"><?= e($errors['sort_order']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">
                        Lower numbers appear first.<?= $isEdit ? ' Leave blank to keep the current order.' : ' Leave blank to append at the end.' ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create gallery item' ?></button>
                <a href="<?= e(url('/admin/gallery')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
