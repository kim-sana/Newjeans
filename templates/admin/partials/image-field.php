<?php

$fieldName = (string) ($name ?? 'image');
$fieldLabel = (string) ($label ?? 'Image');
$fieldCurrent = (string) ($current ?? '');
$fieldRequired = (bool) ($required ?? false);
$fieldError = $error ?? null;
$fieldHelp = (string) ($help ?? '');
?>
<div class="mb-3">
    <label class="form-label" for="<?= e($fieldName) ?>"><?= e($fieldLabel) ?></label>
    <input
        type="file"
        id="<?= e($fieldName) ?>"
        name="<?= e($fieldName) ?>"
        class="form-control<?= is_string($fieldError) && $fieldError !== '' ? ' is-invalid' : '' ?>"
        accept="image/jpeg,image/png,image/webp,image/gif"
        <?= $fieldRequired ? 'required' : '' ?>
    >
    <?php if (is_string($fieldError) && $fieldError !== ''): ?>
        <div class="invalid-feedback d-block"><?= e($fieldError) ?></div>
    <?php endif; ?>
    <?php if ($fieldHelp !== ''): ?>
        <div class="form-text"><?= e($fieldHelp) ?></div>
    <?php endif; ?>
    <?php if ($fieldCurrent !== ''): ?>
        <div class="mt-2">
            <img src="<?= e(asset($fieldCurrent)) ?>" alt="Current <?= e(strtolower($fieldLabel)) ?>" class="admin-preview">
        </div>
    <?php endif; ?>
</div>
