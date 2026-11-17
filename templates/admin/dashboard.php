<?php

$statCards = is_array($counts ?? null) ? $counts : [];
$publishedNewsCount = (int) ($publishedNews ?? 0);
$featuredSongsCount = (int) ($featuredSongs ?? 0);
?>
<div class="row g-3">
    <?php foreach ($statCards as $card): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="small-box text-bg-<?= e($card['theme'] ?? 'primary') ?>">
                <div class="inner">
                    <h3><?= (int) ($card['count'] ?? 0) ?></h3>
                    <p><?= e($card['label'] ?? '') ?></p>
                </div>
                <i class="small-box-icon bi <?= e($card['icon'] ?? 'bi-collection') ?>"></i>
            </div>
        </div>
    <?php endforeach; ?>
</div>
