<?php

$itemRows = is_array($items ?? null) ? $items : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-images me-1"></i> Gallery</h3>
        <a href="<?= e(url('/admin/gallery/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New gallery item
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($itemRows === []): ?>
            <p class="admin-empty mb-0">No gallery items yet. Upload the first photo.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Alt text</th>
                            <th>Caption</th>
                            <th class="text-center">Sort order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemRows as $itemRow): ?>
                            <?php
                            $itemId = (int) ($itemRow['id'] ?? 0);
                            $image = (string) ($itemRow['image'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <?php if ($image !== ''): ?>
                                        <img src="<?= e(asset($image)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($itemRow['alt_text'] ?? '') ?></td>
                                <td class="text-secondary"><?= e($itemRow['caption'] ?? '') ?></td>
                                <td class="text-center"><?= (int) ($itemRow['sort_order'] ?? 0) ?></td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/gallery/' . $itemId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/gallery/' . $itemId . '/delete'),
                                        'confirm' => 'Delete this gallery item?',
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
