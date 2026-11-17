<?php

$flashSuccess = flash('success');
$flashError = flash('error');
?>
<?php if ($flashSuccess !== null || $flashError !== null): ?>
    <div class="container" style="padding-top: calc(var(--nav-height, 72px) + 1rem); position: relative; z-index: 2;">
        <?php if ($flashSuccess !== null): ?>
            <p role="status" style="padding: 0.85rem 1rem; border-radius: 12px; background: #e8f4ff; color: #005bab; font-weight: 600;">
                <?= e($flashSuccess) ?>
            </p>
        <?php endif; ?>
        <?php if ($flashError !== null): ?>
            <p role="alert" style="padding: 0.85rem 1rem; border-radius: 12px; background: #ffecec; color: #b42318; font-weight: 600;">
                <?= e($flashError) ?>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>
