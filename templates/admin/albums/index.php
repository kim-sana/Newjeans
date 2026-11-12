<?php

$albumRows = is_array($albums ?? null) ? $albums : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-vinyl me-1"></i> Albums</h3>
        <a href="<?= e(url('/admin/albums/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New album
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($albumRows === []): ?>
            <p class="admin-empty mb-0">No albums yet. Create the first release.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Album</th>
                            <th>Type</th>
                            <th>Release</th>
                            <th class="text-center">Songs</th>
                            <th>Price</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">For sale</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albumRows as $albumRow): ?>
                            <?php
                            $albumId = (int) ($albumRow['id'] ?? 0);
                            $cover = (string) ($albumRow['cover_album'] ?? '');
                            $release = $albumRow['release_date'] ?? null;
                            $forSale = (int) ($albumRow['is_for_sale'] ?? 0) === 1;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($cover !== ''): ?>
                                        <img src="<?= e(asset($cover)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($albumRow['name'] ?? '') ?></td>
                                <td>
                                    <span class="badge text-bg-primary"><?= e($albumRow['type_name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?= is_string($release) && $release !== ''
                                        ? e(date('M j, Y', (int) strtotime($release)))
                                        : '<span class="text-secondary">&mdash;</span>' ?>
                                </td>
                                <td class="text-center"><?= (int) ($albumRow['song_count'] ?? 0) ?></td>
                                <td>$<?= e(number_format((float) ($albumRow['price'] ?? 0), 2)) ?></td>
                                <td class="text-center"><?= (int) ($albumRow['stock'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if ($forSale): ?>
                                        <span class="badge text-bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/albums/' . $albumId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <a
                                        href="<?= e(url('/admin/albums/' . $albumId . '/songs')) ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >Songs</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/albums/' . $albumId . '/delete'),
                                        'confirm' => 'Delete album "' . ($albumRow['name'] ?? '') . '"? Albums that still have songs cannot be deleted.',
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
