<?php

$albumRow = is_array($album ?? null) ? $album : [];
$songRows = is_array($songs ?? null) ? $songs : [];
$albumId = (int) ($albumRow['id'] ?? 0);
$albumCover = (string) ($albumRow['cover_album'] ?? '');
$albumRelease = $albumRow['release_date'] ?? null;
?>
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <?php if ($albumCover !== ''): ?>
            <img src="<?= e(asset($albumCover)) ?>" alt="" class="admin-thumb" style="width:72px;height:72px">
        <?php endif; ?>
        <div class="flex-grow-1">
            <div class="text-secondary small">
                <a href="<?= e(url('/admin/albums')) ?>">Albums</a>
                <span class="mx-1">/</span>
                Songs
            </div>
            <h3 class="mb-0"><?= e($albumRow['name'] ?? '') ?></h3>
            <div class="text-secondary small">
                <?= e($albumRow['type_name'] ?? '') ?>
                <?php if (is_string($albumRelease) && $albumRelease !== ''): ?>
                    &middot; <?= e(date('M j, Y', (int) strtotime($albumRelease))) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/albums/' . $albumId . '/edit')) ?>" class="btn btn-outline-secondary btn-sm">Edit album</a>
            <a href="<?= e(url('/admin/albums/' . $albumId . '/songs/create')) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New song
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="bi bi-music-note-beamed me-1"></i> Track list</h3>
    </div>
    <div class="card-body p-0">
        <?php if ($songRows === []): ?>
            <p class="admin-empty mb-0">This album has no songs yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Title</th>
                            <th class="text-center">Title track</th>
                            <th class="text-center">Featured</th>
                            <th>Audio</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songRows as $songRow): ?>
                            <?php
                            $songId = (int) ($songRow['id'] ?? 0);
                            $isTitleTrack = (int) ($songRow['is_title'] ?? 0) === 1;
                            $isFeatured = (int) ($songRow['is_featured'] ?? 0) === 1;
                            $audioUrl = (string) ($songRow['audio_url'] ?? '');
                            ?>
                            <tr>
                                <td class="text-center"><?= (int) ($songRow['track_number'] ?? 0) ?></td>
                                <td class="fw-semibold"><?= e($songRow['title'] ?? '') ?></td>
                                <td class="text-center">
                                    <?php if ($isTitleTrack): ?>
                                        <span class="badge text-bg-primary">Title</span>
                                    <?php else: ?>
                                        <span class="text-secondary">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isFeatured): ?>
                                        <span class="badge text-bg-success">Featured</span>
                                    <?php else: ?>
                                        <span class="text-secondary">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($audioUrl !== ''): ?>
                                        <code class="small"><?= e($audioUrl) ?></code>
                                    <?php else: ?>
                                        <span class="text-secondary">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/albums/' . $albumId . '/songs/' . $songId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/albums/' . $albumId . '/songs/' . $songId . '/delete'),
                                        'confirm' => 'Delete song "' . ($songRow['title'] ?? '') . '"?',
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
