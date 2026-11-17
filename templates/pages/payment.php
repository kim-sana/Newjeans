<?php

$orderRow = is_array($order ?? null) ? $order : [];
$orderItems = is_array($items ?? null) ? $items : [];
$orderToken = is_string($token ?? null) ? $token : '';
$status = (string) ($orderRow['status'] ?? 'PENDING_PAYMENT');
$invoice = (string) ($orderRow['invoice_number'] ?? '');
$subtotal = (float) ($orderRow['subtotal'] ?? 0);
$shipping = (float) ($orderRow['shipping_amount'] ?? 0);
$total = (float) ($orderRow['total'] ?? 0);
?>
<section class="page-hero dot-blue cart-hero">
    <div class="container">
        <p class="eyebrow">Order payment</p>
        <h1 class="page-title"><?= e($invoice !== '' ? $invoice : 'Payment') ?></h1>
        <p class="page-lead">
            Review your order total and complete the simulated payment step.
        </p>
    </div>
</section>

<section class="section dot-paper cart-section" aria-label="Simulated payment">
    <div class="container">
        <div class="payment-layout">
            <div class="payment-panel card">
                <?php if ($status === 'CANCELLED'): ?>
                    <div class="order-status-banner order-status-banner--cancelled">
                        <span class="status-badge status-badge--cancelled">Cancelled</span>
                        <p class="order-status-copy">
                            <?= e((string) ($orderRow['cancel_reason'] ?? 'This order has been cancelled and can no longer be paid.')) ?>
                        </p>
                    </div>
                    <div class="payment-actions">
                        <a class="btn btn--primary" href="<?= e(url('/cart')) ?>">View cart</a>
                        <a class="btn btn--ghost" href="<?= e(url('/discography')) ?>">Browse discography</a>
                    </div>
                <?php else: ?>
                    <div class="order-status-banner order-status-banner--pending">
                        <span class="status-badge status-badge--pending">Pending payment</span>
                        <p class="order-status-copy">
                            Amount due: <strong><?= e(money($total)) ?></strong>
                        </p>
                    </div>

                    <p class="checkout-notice">
                        Simulation only: no real payment is processed and no card details are collected.
                        Choose an outcome below to test the order flow.
                    </p>

                    <div class="payment-actions">
                        <form method="post" action="<?= e(url('/checkout/' . $orderToken . '/pay')) ?>">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="outcome" value="success">
                            <button class="btn btn--primary" type="submit">Simulate payment success</button>
                        </form>

                        <form method="post" action="<?= e(url('/checkout/' . $orderToken . '/pay')) ?>">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="outcome" value="failure">
                            <button class="btn btn--ghost" type="submit">Simulate payment failure</button>
                        </form>
                    </div>

                    <p class="payment-status-link">
                        <a href="<?= e(url('/orders/' . $orderToken)) ?>">View order status page</a>
                    </p>
                <?php endif; ?>
            </div>

            <aside class="checkout-summary card card--cut" aria-label="Order summary">
                <p class="eyebrow">Order summary</p>

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
                        <dt>Shipping estimate</dt>
                        <dd><?= e(money($shipping)) ?></dd>
                    </div>
                    <div class="cart-summary-total">
                        <dt>Total</dt>
                        <dd><?= e(money($total)) ?></dd>
                    </div>
                </dl>

                <p class="cart-summary-note">
                    Shipping to <?= e((string) ($orderRow['destination_city'] ?? '')) ?>,
                    <?= e((string) ($orderRow['destination_country'] ?? '')) ?>
                    (<?= e(number_format((float) ($orderRow['distance_km'] ?? 0), 2)) ?> km from Seoul).
                </p>
            </aside>
        </div>
    </div>
</section>
