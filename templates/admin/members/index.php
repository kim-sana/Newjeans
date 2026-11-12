<?php

$memberRows = is_array($members ?? null) ? $members : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Members</h3>
        <a href="<?= e(url('/admin/members/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New member
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($memberRows === []): ?>
            <p class="admin-empty mb-0">No members yet. Add the first profile.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Roles</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberRows as $memberRow): ?>
                            <?php
                            $memberId = (int) ($memberRow['id'] ?? 0);
                            $photo = (string) ($memberRow['photo'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <?php if ($photo !== ''): ?>
                                        <img src="<?= e(asset($photo)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-person"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($memberRow['name'] ?? '') ?></td>
                                <td><?= e($memberRow['member_roles'] ?? '') ?></td>
                                <td class="text-secondary"><?= e($memberRow['description'] ?? '') ?></td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/members/' . $memberId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/members/' . $memberId . '/delete'),
                                        'confirm' => 'Delete member "' . ($memberRow['name'] ?? '') . '"?',
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
