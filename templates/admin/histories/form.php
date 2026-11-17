<?php

$errors = form_errors();
$historyModel = is_array($model ?? null) ? $model : [];
$historyId = isset($historyModel['id']) ? (int) $historyModel['id'] : 0;
$isEdit = $historyId > 0;

$titleValue = (string) old('title', $historyModel['title'] ?? '');
$descriptionValue = (string) old('description', $historyModel['description'] ?? '');
$imageValue = (string) old('image', $historyModel['image'] ?? '');

$eventDateRaw = (string) old('event_date', $historyModel['event_date'] ?? '');
$eventDateValue = '';
if ($eventDateRaw !== '') {
    $eventTimestamp = strtotime($eventDateRaw);
    $eventDateValue = $eventTimestamp === false ? $eventDateRaw : date('Y-m-d', $eventTimestamp);
}

$action = $isEdit ? url('/admin/histories/' . $historyId) : url('/admin/histories');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit history entry' : 'New history entry' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="historyTitle" class="form-label">Title</label>
                    <input
                        type="text"
                        id="historyTitle"
                        name="title"
                        value="<?= e($titleValue) ?>"
                        class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>"
                        maxlength="160"
                        required
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="historyDate" class="form-label">Event date</label>
                    <input
                        type="date"
                        id="historyDate"
                        name="event_date"
                        value="<?= e($eventDateValue) ?>"
                        class="form-control<?= isset($errors['event_date']) ? ' is-invalid' : '' ?>"
                    >
                    <?php if (isset($errors['event_date'])): ?>
                        <div class="invalid-feedback"><?= e($errors['event_date']) ?></div>
                    <?php endif; ?>
                </div>

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

                <div class="col-12">
                    <label for="historyDescription" class="form-label">Description</label>
                    <textarea
                        id="historyDescription"
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
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create history' ?></button>
                <a href="<?= e(url('/admin/histories')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
