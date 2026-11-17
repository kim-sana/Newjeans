<?php

$orderRow = is_array($order ?? null) ? $order : [];
$orderItems = is_array($items ?? null) ? $items : [];
$status = (string) ($orderRow['status'] ?? 'PENDING_PAYMENT');
$region = is_string($orderRow['destination_region'] ?? null) ? trim((string) $orderRow['destination_region']) : '';

$badge = match ($status) {
    'PAID' => ['class' => 'success', 'label' => 'Paid'],
    'CANCELLED' => ['class' => 'danger', 'label' => 'Cancelled'],
    default => ['class' => 'warning', 'label' => 'Pending payment'],
};
?>
<div class="mb-3">
    <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to orders
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">
                    <i class="bi bi-receipt me-1"></i>
                    <code><?= e($orderRow['invoice_number'] ?? '') ?></code>
                </h3>
                <span class="badge text-bg-<?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Payment provider</dt>
                    <dd class="col-sm-8"><?= e($orderRow['payment_provider'] ?? 'mock') ?></dd>

                    <dt class="col-sm-4">Created at</dt>
                    <dd class="col-sm-8"><?= !empty($orderRow['created_at']) ? e(date('M j, Y H:i:s', (int) strtotime((string) $orderRow['created_at']))) : '&mdash;' ?></dd>

                    <?php if (!empty($orderRow['paid_at'])): ?>
                        <dt class="col-sm-4">Paid at</dt>
                        <dd class="col-sm-8"><?= e(date('M j, Y H:i:s', (int) strtotime((string) $orderRow['paid_at']))) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($orderRow['cancelled_at'])): ?>
                        <dt class="col-sm-4">Cancelled at</dt>
                        <dd class="col-sm-8"><?= e(date('M j, Y H:i:s', (int) strtotime((string) $orderRow['cancelled_at']))) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($orderRow['cancel_reason'])): ?>
                        <dt class="col-sm-4">Cancel reason</dt>
                        <dd class="col-sm-8 text-danger"><?= e((string) $orderRow['cancel_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-vinyl me-1"></i> Item snapshots</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Album</th>
                                <th class="text-end">Unit price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($item['album_name'] ?? '') ?></td>
                                    <td class="text-end"><?= e(money((float) ($item['unit_price'] ?? 0))) ?></td>
                                    <td class="text-center"><?= (int) ($item['quantity'] ?? 0) ?></td>
                                    <td class="text-end fw-semibold"><?= e(money((float) ($item['line_total'] ?? 0))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal</th>
                                <td class="text-end"><?= e(money((float) ($orderRow['subtotal'] ?? 0))) ?></td>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Shipping</th>
                                <td class="text-end"><?= e(money((float) ($orderRow['shipping_amount'] ?? 0))) ?></td>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <td class="text-end fw-bold"><?= e(money((float) ($orderRow['total'] ?? 0))) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-person me-1"></i> Recipient</h3>
            </div>
            <div class="card-body">
                <p class="fw-semibold mb-1"><?= e($orderRow['recipient_name'] ?? '') ?></p>
                <p class="text-secondary mb-2"><?= e($orderRow['recipient_phone'] ?? '') ?></p>
                <p class="mb-0"><?= e($orderRow['shipping_address'] ?? '') ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-geo-alt me-1"></i> Destination</h3>
            </div>
            <div class="card-body">
                <p class="fw-semibold mb-1">
                    <?= e($orderRow['destination_city'] ?? '') ?><?= $region !== '' ? ', ' . e($region) : '' ?>
                </p>
                <p class="text-secondary mb-2">
                    <?= e($orderRow['destination_country'] ?? '') ?> (<code><?= e($orderRow['destination_country_code'] ?? '') ?></code>)
                </p>
                <p class="small text-secondary mb-1">
                    Coordinates: <?= e((string) ($orderRow['destination_latitude'] ?? '')) ?>,
                    <?= e((string) ($orderRow['destination_longitude'] ?? '')) ?>
                </p>
                <p class="small text-secondary mb-0">
                    Distance: <?= e(number_format((float) ($orderRow['distance_km'] ?? 0), 2)) ?> km from Seoul
                </p>
            </div>
        </div>
    </div>
</div>
