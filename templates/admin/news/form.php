<?php

$errors = form_errors();
$newsModel = is_array($model ?? null) ? $model : [];
$newsId = isset($newsModel['id']) ? (int) $newsModel['id'] : 0;
$isEdit = $newsId > 0;

$slugValue = (string) old('slug', $newsModel['slug'] ?? '');
$titleValue = (string) old('title', $newsModel['title'] ?? '');
$thumbnailValue = (string) old('thumbnail', $newsModel['thumbnail'] ?? '');
$contentValue = (string) old('content', $newsModel['content'] ?? '');
$statusValue = (string) old('status', $newsModel['status'] ?? 'draft');
$sourceNameValue = (string) old('source_name', $newsModel['source_name'] ?? '');
$sourceUrlValue = (string) old('source_url', $newsModel['source_url'] ?? '');

$publishedRaw = (string) old('published_at', $newsModel['published_at'] ?? '');
$publishedValue = '';
if ($publishedRaw !== '') {
    $publishedTimestamp = strtotime($publishedRaw);
    $publishedValue = $publishedTimestamp === false ? $publishedRaw : date('Y-m-d\TH:i', $publishedTimestamp);
}

$statusOptions = [
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived',
];

$action = $isEdit ? url('/admin/news/' . $newsId) : url('/admin/news');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit news item' : 'New news item' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="newsTitle" class="form-label">Title</label>
                    <input
                        type="text"
                        id="newsTitle"
                        name="title"
                        value="<?= e($titleValue) ?>"
                        class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>"
                        maxlength="200"
                        required
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="newsStatus" class="form-label">Status</label>
                    <select
                        id="newsStatus"
                        name="status"
                        class="form-select<?= isset($errors['status']) ? ' is-invalid' : '' ?>"
                        required
                    >
                        <?php foreach ($statusOptions as $optionValue => $optionLabel): ?>
                            <option value="<?= e($optionValue) ?>" <?= $optionValue === $statusValue ? 'selected' : '' ?>>
                                <?= e($optionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                        <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label for="newsSlug" class="form-label">Slug</label>
                    <input
                        type="text"
                        id="newsSlug"
                        name="slug"
                        value="<?= e($slugValue) ?>"
                        class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>"
                        maxlength="190"
                        placeholder="newjeans-cover-the-face"
                    >
                    <?php if (isset($errors['slug'])): ?>
                        <div class="invalid-feedback"><?= e($errors['slug']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Leave blank to generate from the title. Slugs are kept unique automatically.</div>
                </div>

                <div class="col-md-4">
                    <label for="newsPublished" class="form-label">Published at</label>
                    <input
                        type="datetime-local"
                        id="newsPublished"
                        name="published_at"
                        value="<?= e($publishedValue) ?>"
                        class="form-control<?= isset($errors['published_at']) ? ' is-invalid' : '' ?>"
                    >
                    <?php if (isset($errors['published_at'])): ?>
                        <div class="invalid-feedback"><?= e($errors['published_at']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Published status with an empty date uses the current time.</div>
                </div>

                <div class="col-12">
                    <?= \App\Core\View::render('admin/partials/image-field', [
                        'name' => 'thumbnail',
                        'label' => 'Thumbnail',
                        'current' => $thumbnailValue,
                        'required' => !$isEdit,
                        'error' => $errors['thumbnail'] ?? null,
                        'help' => 'JPEG, PNG, WebP, or GIF up to 5 MB.' . ($isEdit ? ' Leave empty to keep the current thumbnail.' : ''),
                    ]) ?>
                </div>

                <div class="col-12">
                    <label for="newsContent" class="form-label">Content</label>
                    <textarea
                        id="newsContent"
                        name="content"
                        rows="10"
                        class="form-control<?= isset($errors['content']) ? ' is-invalid' : '' ?>"
                        required
                    ><?= e($contentValue) ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                        <div class="invalid-feedback"><?= e($errors['content']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="newsSourceName" class="form-label">Source name</label>
                    <input
                        type="text"
                        id="newsSourceName"
                        name="source_name"
                        value="<?= e($sourceNameValue) ?>"
                        class="form-control<?= isset($errors['source_name']) ? ' is-invalid' : '' ?>"
                        maxlength="120"
                        placeholder="Korea Herald"
                    >
                    <?php if (isset($errors['source_name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['source_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="newsSourceUrl" class="form-label">Source URL</label>
                    <input
                        type="url"
                        id="newsSourceUrl"
                        name="source_url"
                        value="<?= e($sourceUrlValue) ?>"
                        class="form-control<?= isset($errors['source_url']) ? ' is-invalid' : '' ?>"
                        maxlength="255"
                        placeholder="https://example.com/article"
                    >
                    <?php if (isset($errors['source_url'])): ?>
                        <div class="invalid-feedback"><?= e($errors['source_url']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create news item' ?></button>
                <a href="<?= e(url('/admin/news')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
