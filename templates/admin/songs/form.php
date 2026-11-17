<?php

$errors = form_errors();
$albumRow = is_array($album ?? null) ? $album : [];
$songModel = is_array($model ?? null) ? $model : [];
$albumId = (int) ($albumRow['id'] ?? 0);
$songId = isset($songModel['id']) ? (int) $songModel['id'] : 0;
$isEdit = $songId > 0;

$titleValue = (string) old('title', $songModel['title'] ?? '');
$audioValue = (string) old('audio_url', $songModel['audio_url'] ?? '');
$isFeatured = (int) old('is_featured', $songModel['is_featured'] ?? 0) === 1;

$basePath = '/admin/albums/' . $albumId . '/songs';
$action = $isEdit ? url($basePath . '/' . $songId) : url($basePath);
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <?= $isEdit ? 'Edit song' : 'New song' ?>
            <span class="text-secondary fw-normal">&middot; <?= e($albumRow['name'] ?? '') ?></span>
        </h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <label for="songTitle" class="form-label">Title</label>
                    <input
                        type="text"
                        id="songTitle"
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

                <div class="col-12">
                    <label for="songAudio" class="form-label">Audio URL / path</label>
                    <input
                        type="text"
                        id="songAudio"
                        name="audio_url"
                        value="<?= e($audioValue) ?>"
                        class="form-control<?= isset($errors['audio_url']) ? ' is-invalid' : '' ?>"
                        maxlength="255"
                        placeholder="audio/Super-Shy.mp3"
                    >
                    <?php if (isset($errors['audio_url'])): ?>
                        <div class="invalid-feedback"><?= e($errors['audio_url']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Path relative to assets (for example <code>audio/ETA.mp3</code>) or a full URL.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            role="switch"
                            id="songIsFeatured"
                            name="is_featured"
                            value="1"
                            class="form-check-input"
                            <?= $isFeatured ? 'checked' : '' ?>
                        >
                        <label for="songIsFeatured" class="form-check-label">Featured</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create song' ?></button>
                <a href="<?= e(url($basePath)) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
