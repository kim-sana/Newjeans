<?php

$formErrorList = is_array($errors ?? null)
    ? array_values(array_filter($errors, static fn (mixed $message): bool => is_string($message) && $message !== ''))
    : [];
?>
<?php if ($formErrorList !== []): ?>
    <div class="form-errors" role="alert">
        <p class="form-errors-title">Please fix the following:</p>
        <ul class="form-errors-list">
            <?php foreach ($formErrorList as $formErrorMessage): ?>
                <li><?= e($formErrorMessage) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
