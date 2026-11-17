<?php

$newsRows = is_array($newsItems ?? null) ? $newsItems : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-newspaper me-1"></i> News</h3>
        <a href="<?= e(url('/admin/news/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New news item
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($newsRows === []): ?>
            <p class="admin-empty mb-0">No news items yet. Publish the first story.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Thumbnail</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsRows as $newsRow): ?>
                            <?php
                            $newsId = (int) ($newsRow['id'] ?? 0);
                            $thumbnail = (string) ($newsRow['thumbnail'] ?? '');
                            $status = (string) ($newsRow['status'] ?? 'draft');
                            $publishedAt = $newsRow['published_at'] ?? null;
                            $statusTheme = match ($status) {
                                'published' => 'success',
                                'archived' => 'warning',
                                default => 'secondary',
                            };
                            ?>
                            <tr>
                                <td>
                                    <?php if ($thumbnail !== ''): ?>
                                        <img src="<?= e(asset($thumbnail)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($newsRow['title'] ?? '') ?></td>
                                <td>
                                    <span class="badge text-bg-<?= e($statusTheme) ?>"><?= e(ucfirst($status)) ?></span>
                                </td>
                                <td>
                                    <?= is_string($publishedAt) && $publishedAt !== ''
                                        ? e(date('M j, Y', (int) strtotime($publishedAt)))
                                        : '<span class="text-secondary">&mdash;</span>' ?>
                                </td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/news/' . $newsId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/news/' . $newsId . '/delete'),
                                        'confirm' => 'Delete news item "' . ($newsRow['title'] ?? '') . '"?',
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
