<?php

$errors = form_errors();
$albumModel = is_array($model ?? null) ? $model : [];
$albumId = isset($albumModel['id']) ? (int) $albumModel['id'] : 0;
$isEdit = $albumId > 0;
$typeOptions = is_array($types ?? null) ? $types : [];

$nameValue = (string) old('name', $albumModel['name'] ?? '');
$typeValue = (string) old('album_type_id', $albumModel['album_type_id'] ?? '');
$coverValue = (string) old('cover_album', $albumModel['cover_album'] ?? '');
$spotifyValue = (string) old('spotify_url', $albumModel['spotify_url'] ?? '');
$youtubeValue = (string) old('youtube_url', $albumModel['youtube_url'] ?? '');
$priceValue = (string) old('price', $albumModel['price'] ?? '0.00');
$stockValue = (string) old('stock', $albumModel['stock'] ?? '0');
$isForSale = (int) old('is_for_sale', $albumModel['is_for_sale'] ?? 0) === 1;

$releaseRaw = (string) old('release_date', $albumModel['release_date'] ?? '');
$releaseValue = '';
if ($releaseRaw !== '') {
    $releaseTimestamp = strtotime($releaseRaw);
    $releaseValue = $releaseTimestamp === false ? $releaseRaw : date('Y-m-d', $releaseTimestamp);
}

$action = $isEdit ? url('/admin/albums/' . $albumId) : url('/admin/albums');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit album' : 'New album' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="albumName" class="form-label">Name</label>
                    <input
                        type="text"
                        id="albumName"
                        name="name"
                        value="<?= e($nameValue) ?>"
                        class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                        maxlength="160"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="albumType" class="form-label">Album type</label>
                    <select
                        id="albumType"
                        name="album_type_id"
                        class="form-select<?= isset($errors['album_type_id']) ? ' is-invalid' : '' ?>"
                        required
                    >
                        <option value="">Select a type</option>
                        <?php foreach ($typeOptions as $typeOption): ?>
                            <?php $optionId = (string) ($typeOption['id'] ?? ''); ?>
                            <option value="<?= e($optionId) ?>" <?= $optionId === $typeValue ? 'selected' : '' ?>>
                                <?= e($typeOption['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['album_type_id'])): ?>
                        <div class="invalid-feedback"><?= e($errors['album_type_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <?= \App\Core\View::render('admin/partials/image-field', [
                        'name' => 'cover_album',
                        'label' => 'Cover image',
                        'current' => $coverValue,
                        'required' => !$isEdit,
                        'error' => $errors['cover_album'] ?? null,
                        'help' => 'JPEG, PNG, WebP, or GIF up to 5 MB.' . ($isEdit ? ' Leave empty to keep the current cover.' : ''),
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <label for="albumRelease" class="form-label">Release date</label>
                    <input
                        type="date"
                        id="albumRelease"
                        name="release_date"
                        value="<?= e($releaseValue) ?>"
                        class="form-control<?= isset($errors['release_date']) ? ' is-invalid' : '' ?>"
                    >
                    <?php if (isset($errors['release_date'])): ?>
                        <div class="invalid-feedback"><?= e($errors['release_date']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="albumPrice" class="form-label">Price (USD)</label>
                    <input
                        type="number"
                        id="albumPrice"
                        name="price"
                        value="<?= e($priceValue) ?>"
                        class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>"
                        min="0"
                        step="0.01"
                        required
                    >
                    <?php if (isset($errors['price'])): ?>
                        <div class="invalid-feedback"><?= e($errors['price']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label for="albumStock" class="form-label">Stock</label>
                    <input
                        type="number"
                        id="albumStock"
                        name="stock"
                        value="<?= e($stockValue) ?>"
                        class="form-control<?= isset($errors['stock']) ? ' is-invalid' : '' ?>"
                        min="0"
                        step="1"
                        required
                    >
                    <?php if (isset($errors['stock'])): ?>
                        <div class="invalid-feedback"><?= e($errors['stock']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="albumSpotify" class="form-label">Spotify URL</label>
                    <input
                        type="url"
                        id="albumSpotify"
                        name="spotify_url"
                        value="<?= e($spotifyValue) ?>"
                        class="form-control<?= isset($errors['spotify_url']) ? ' is-invalid' : '' ?>"
                        maxlength="255"
                        placeholder="https://open.spotify.com/album/..."
                    >
                    <?php if (isset($errors['spotify_url'])): ?>
                        <div class="invalid-feedback"><?= e($errors['spotify_url']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="albumYoutube" class="form-label">YouTube URL</label>
                    <input
                        type="url"
                        id="albumYoutube"
                        name="youtube_url"
                        value="<?= e($youtubeValue) ?>"
                        class="form-control<?= isset($errors['youtube_url']) ? ' is-invalid' : '' ?>"
                        maxlength="255"
                        placeholder="https://www.youtube.com/watch?v=..."
                    >
                    <?php if (isset($errors['youtube_url'])): ?>
                        <div class="invalid-feedback"><?= e($errors['youtube_url']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            id="albumForSale"
                            name="is_for_sale"
                            value="1"
                            class="form-check-input"
                            <?= $isForSale ? 'checked' : '' ?>
                        >
                        <label for="albumForSale" class="form-check-label">Available for sale</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create album' ?></button>
                <a href="<?= e(url('/admin/albums')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
