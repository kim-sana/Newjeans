<?php

$formErrorList = is_array($errors ?? null) ? $errors : [];
?>
<?php if ($formErrorList !== []): ?>
    <div class="alert alert-danger" role="alert">
        <p class="fw-semibold mb-1">Please fix the following:</p>
        <ul class="mb-0 ps-3">
            <?php foreach ($formErrorList as $formErrorMessage): ?>
                <li><?= e($formErrorMessage) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
