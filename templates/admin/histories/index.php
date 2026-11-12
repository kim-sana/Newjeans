<?php

$historyRows = is_array($histories ?? null) ? $histories : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Histories</h3>
        <a href="<?= e(url('/admin/histories/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New history
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($historyRows === []): ?>
            <p class="admin-empty mb-0">No history entries yet. Add the first milestone.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Event date</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <?php
                            $historyId = (int) ($historyRow['id'] ?? 0);
                            $image = (string) ($historyRow['image'] ?? '');
                            $eventDate = $historyRow['event_date'] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($image !== ''): ?>
                                        <img src="<?= e(asset($image)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($historyRow['title'] ?? '') ?></td>
                                <td>
                                    <?= is_string($eventDate) && $eventDate !== ''
                                        ? e(date('M j, Y', (int) strtotime($eventDate)))
                                        : '<span class="text-secondary">&mdash;</span>' ?>
                                </td>
                                <td class="text-secondary"><?= e($historyRow['description'] ?? '') ?></td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/histories/' . $historyId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/histories/' . $historyId . '/delete'),
                                        'confirm' => 'Delete history entry "' . ($historyRow['title'] ?? '') . '"?',
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
