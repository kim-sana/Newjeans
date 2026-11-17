<?php

$paginationData = is_array($pagination ?? null) ? $pagination : [];
$orderRows = is_array($paginationData['rows'] ?? null) ? $paginationData['rows'] : [];
$currentPage = max(1, (int) ($paginationData['page'] ?? 1));
$totalPages = max(0, (int) ($paginationData['pages'] ?? 0));
$totalOrders = max(0, (int) ($paginationData['total'] ?? 0));
$queryParams = is_array($queryParams ?? null) ? $queryParams : [];

$pageUrl = static function (int $page) use ($queryParams): string {
    $params = $queryParams;
    $params['page'] = $page;

    return url('/admin/orders') . '?' . http_build_query($params);
};

$statusBadge = static function (string $status): array {
    return match ($status) {
        'PAID' => ['class' => 'success', 'label' => 'Paid'],
        'CANCELLED' => ['class' => 'danger', 'label' => 'Cancelled'],
        default => ['class' => 'warning', 'label' => 'Pending payment'],
    };
};
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> Orders</h3>
        <span class="text-secondary small"><?= $totalOrders ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if ($orderRows === []): ?>
            <p class="admin-empty mb-0">No orders have been placed yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Destination</th>
                            <th>Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderRows as $orderRow): ?>
                            <?php
                            $orderId = (int) ($orderRow['id'] ?? 0);
                            $createdAt = (string) ($orderRow['created_at'] ?? '');
                            $badge = $statusBadge((string) ($orderRow['status'] ?? 'PENDING_PAYMENT'));
                            ?>
                            <tr>
                                <td class="fw-semibold"><code><?= e($orderRow['invoice_number'] ?? '') ?></code></td>
                                <td><?= $createdAt !== '' ? e(date('M j, Y H:i', (int) strtotime($createdAt))) : '&mdash;' ?></td>
                                <td><?= e($orderRow['recipient_name'] ?? '') ?></td>
                                <td>
                                    <?= e($orderRow['destination_city'] ?? '') ?>,
                                    <?= e($orderRow['destination_country'] ?? '') ?>
                                </td>
                                <td class="fw-semibold"><?= e(money((float) ($orderRow['total'] ?? 0))) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                                </td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/orders/' . $orderId)) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer clearfix">
            <ul class="pagination pagination-sm m-0 float-end">
                <li class="page-item<?= $currentPage <= 1 ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(max(1, $currentPage - 1))) ?>">&laquo;</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item<?= $p === $currentPage ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item<?= $currentPage >= $totalPages ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(min($totalPages, $currentPage + 1))) ?>">&raquo;</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>
