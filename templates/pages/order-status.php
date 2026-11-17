<?php

$orderRow = is_array($order ?? null) ? $order : [];
$orderItems = is_array($items ?? null) ? $items : [];
$orderToken = is_string($token ?? null) ? $token : '';
$status = (string) ($orderRow['status'] ?? 'PENDING_PAYMENT');
$invoice = (string) ($orderRow['invoice_number'] ?? '');
$subtotal = (float) ($orderRow['subtotal'] ?? 0);
$shipping = (float) ($orderRow['shipping_amount'] ?? 0);
$total = (float) ($orderRow['total'] ?? 0);
$region = is_string($orderRow['destination_region'] ?? null) ? trim((string) $orderRow['destination_region']) : '';

$statusMeta = match ($status) {
    'PAID' => ['class' => 'paid', 'label' => 'Paid'],
    'CANCELLED' => ['class' => 'cancelled', 'label' => 'Cancelled'],
    default => ['class' => 'pending', 'label' => 'Pending payment'],
};
?>
<section class="page-hero dot-blue cart-hero">
    <div class="container">
        <p class="eyebrow">Order status</p>
        <h1 class="page-title"><?= e($invoice !== '' ? $invoice : 'Order') ?></h1>
        <p class="page-lead">
            Order receipt and shipping snapshot.
        </p>
    </div>
</section>

<section class="section dot-paper cart-section" aria-label="Order status">
    <div class="container">
        <div class="payment-layout">
            <div class="payment-panel card">
                <div class="order-status-banner order-status-banner--<?= e($statusMeta['class']) ?>">
                    <span class="status-badge status-badge--<?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                    <p class="order-status-copy">
                        <?php if ($status === 'PAID'): ?>
                            Confirmed<?= !empty($orderRow['paid_at']) ? ' on ' . e(format_date((string) $orderRow['paid_at'], 'M j, Y H:i')) : '' ?>.
                        <?php elseif ($status === 'CANCELLED'): ?>
                            <?= e((string) ($orderRow['cancel_reason'] ?? 'This order was cancelled.')) ?>
                        <?php else: ?>
                            Awaiting simulated payment confirmation.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="order-details-grid">
                    <div>
                        <h2 class="order-section-title">Recipient</h2>
                        <dl class="order-meta-list">
                            <div>
                                <dt>Name</dt>
                                <dd><?= e((string) ($orderRow['recipient_name'] ?? '')) ?></dd>
                            </div>
                            <div>
                                <dt>Phone</dt>
                                <dd><?= e((string) ($orderRow['recipient_phone'] ?? '')) ?></dd>
                            </div>
                            <div>
                                <dt>Address</dt>
                                <dd><?= e((string) ($orderRow['shipping_address'] ?? '')) ?></dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h2 class="order-section-title">Destination &amp; timestamps</h2>
                        <dl class="order-meta-list">
                            <div>
                                <dt>Destination</dt>
                                <dd>
                                    <?= e((string) ($orderRow['destination_city'] ?? '')) ?><?= $region !== '' ? ', ' . e($region) : '' ?>,
                                    <?= e((string) ($orderRow['destination_country'] ?? '')) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Distance</dt>
                                <dd><?= e(number_format((float) ($orderRow['distance_km'] ?? 0), 2)) ?> km from Seoul</dd>
                            </div>
                            <div>
                                <dt>Placed</dt>
                                <dd><?= e(format_date((string) ($orderRow['created_at'] ?? ''), 'M j, Y H:i')) ?></dd>
                            </div>
                            <?php if (!empty($orderRow['paid_at'])): ?>
                                <div>
                                    <dt>Paid</dt>
                                    <dd><?= e(format_date((string) $orderRow['paid_at'], 'M j, Y H:i')) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($orderRow['cancelled_at'])): ?>
                                <div>
                                    <dt>Cancelled</dt>
                                    <dd><?= e(format_date((string) $orderRow['cancelled_at'], 'M j, Y H:i')) ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>

                <div class="payment-actions">
                    <?php if ($status === 'PENDING_PAYMENT'): ?>
                        <a class="btn btn--primary" href="<?= e(url('/checkout/' . $orderToken . '/payment')) ?>">Complete payment</a>
                    <?php elseif ($status === 'CANCELLED'): ?>
                        <a class="btn btn--primary" href="<?= e(url('/cart')) ?>">Return to cart</a>
                    <?php endif; ?>
                    <a class="btn btn--ghost" href="<?= e(url('/discography')) ?>">Browse discography</a>
                </div>
            </div>

            <aside class="checkout-summary card card--cut" aria-label="Order items">
                <p class="eyebrow">Items</p>

                <ul class="checkout-summary-items">
                    <?php foreach ($orderItems as $item): ?>
                        <li class="checkout-summary-item checkout-summary-item--compact">
                            <div class="checkout-summary-body">
                                <p class="checkout-summary-name"><?= e((string) ($item['album_name'] ?? 'Album')) ?></p>
                                <p class="checkout-summary-qty">
                                    <?= (int) ($item['quantity'] ?? 0) ?> &times; <?= e(money((float) ($item['unit_price'] ?? 0))) ?>
                                </p>
                            </div>
                            <p class="checkout-summary-line"><?= e(money((float) ($item['line_total'] ?? 0))) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <dl class="cart-summary-list">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= e(money($subtotal)) ?></dd>
                    </div>
                    <div>
                        <dt>Shipping</dt>
                        <dd><?= e(money($shipping)) ?></dd>
                    </div>
                    <div class="cart-summary-total">
                        <dt>Total</dt>
                        <dd><?= e(money($total)) ?></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</section>
