<?php

$deleteAction = (string) ($action ?? '');
$deleteConfirm = (string) ($confirm ?? 'Delete this item?');
$deleteLabel = (string) ($label ?? 'Delete');
$deleteConfirmJson = (string) json_encode($deleteConfirm, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
?>
<form
    method="post"
    action="<?= e($deleteAction) ?>"
    class="d-inline"
    onsubmit="return confirm(<?= e($deleteConfirmJson) ?>);"
>
    <?= \App\Core\Csrf::field() ?>
    <button type="submit" class="btn btn-sm btn-outline-danger"><?= e($deleteLabel) ?></button>
</form>
