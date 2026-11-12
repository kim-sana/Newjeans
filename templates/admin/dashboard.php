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

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-speedometer2 me-1"></i> Content overview
                </h3>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    You are signed in to the NewJeans content management panel. Use the sidebar to reach each
                    content module as it becomes available.
                </p>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Published news</dt>
                    <dd class="col-sm-7"><?= $publishedNewsCount ?></dd>
                    <dt class="col-sm-5">Total news (all statuses)</dt>
                    <dd class="col-sm-7"><?= (int) ($statCards[3]['count'] ?? 0) ?></dd>
                    <dt class="col-sm-5">Featured songs</dt>
                    <dd class="col-sm-7"><?= $featuredSongsCount ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100 card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-info-circle me-1"></i> Next up</h3>
            </div>
            <div class="card-body">
                <p class="mb-2">Catalog management is available now. The remaining content modules will follow:</p>
                <ul class="mb-0">
                    <li><span class="badge text-bg-success me-1">Ready</span> Album types, albums, and songs</li>
                    <li>News and events</li>
                    <li>Members, histories, and gallery</li>
                </ul>
            </div>
        </div>
    </div>
</div>
