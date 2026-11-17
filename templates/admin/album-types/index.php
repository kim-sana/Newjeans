<?php

$typeRows = is_array($types ?? null) ? $types : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-tags me-1"></i> Album types</h3>
        <a href="<?= e(url('/admin/album-types/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New type
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($typeRows === []): ?>
            <p class="admin-empty mb-0">No album types yet. Create the first one.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Albums</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($typeRows as $typeRow): ?>
                            <?php $typeId = (int) ($typeRow['id'] ?? 0); ?>
                            <tr>
                                <td class="fw-semibold"><?= e($typeRow['name'] ?? '') ?></td>
                                <td class="text-center"><?= (int) ($typeRow['album_count'] ?? 0) ?></td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/album-types/' . $typeId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/album-types/' . $typeId . '/delete'),
                                        'confirm' => 'Delete album type "' . ($typeRow['name'] ?? '') . '"? Types that still have albums cannot be deleted.',
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
