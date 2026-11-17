<?php

$items = is_array($items ?? null) ? $items : [];
$subtotal = is_numeric($subtotal ?? null) ? (float) $subtotal : 0.0;
$hasIssues = (bool) ($hasIssues ?? false);
?>
<section class="page-hero dot-blue cart-hero">
    <div class="container">
        <p class="eyebrow">Guest checkout</p>
        <h1 class="page-title">Your Cart</h1>
        <p class="page-lead">
            Review your album picks, adjust quantities, then continue to a shipping estimate from Seoul.
        </p>
    </div>
</section>

<section class="section dot-paper cart-section" aria-label="Shopping cart">
    <div class="container">
        <?php if ($items === []): ?>
            <div class="cart-empty card card--cut">
                <p class="cart-empty-title">Your cart is empty.</p>
                <p class="cart-empty-copy">Add an album from the discography to start an order.</p>
                <a class="btn btn--primary" href="<?= e(url('/discography')) ?>">Browse albums</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $albumId = (int) ($item['id'] ?? 0);
                        $name = (string) ($item['name'] ?? 'Album');
                        $cover = (string) ($item['cover'] ?? '');
                        $coverSrc = $cover !== '' ? $cover : 'images/albums/cd.webp';
                        $price = (float) ($item['price'] ?? 0);
                        $quantity = (int) ($item['quantity'] ?? 0);
                        $lineTotal = (float) ($item['line_total'] ?? 0);
                        $maxQuantity = max(1, (int) ($item['max_quantity'] ?? 1));
                        $issue = is_string($item['issue'] ?? null) ? (string) $item['issue'] : '';
                        $issueId = 'cart-issue-' . $albumId;
                        ?>
                        <article class="cart-item card">
                            <a
                                class="cart-item-cover"
                                href="<?= e(url('/discography/' . $albumId)) ?>"
                                aria-label="View <?= e($name) ?> album details">
                                <img
                                    src="<?= e(asset($coverSrc)) ?>"
                                    alt="<?= e($name) ?> album cover"
                                    loading="lazy">
                            </a>

                            <div class="cart-item-body">
                                <div class="cart-item-head">
                                    <div>
                                        <h2 class="cart-item-title">
                                            <a href="<?= e(url('/discography/' . $albumId)) ?>"><?= e($name) ?></a>
                                        </h2>
                                        <p class="cart-item-price"><?= e(money($price)) ?> each</p>
                                    </div>
                                    <p class="cart-item-line-total"><?= e(money($lineTotal)) ?></p>
                                </div>

                                <?php if ($issue !== ''): ?>
                                    <p class="cart-item-warning" id="<?= e($issueId) ?>" role="alert">
                                        <?= e($issue) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="cart-item-actions">
                                    <form class="cart-qty-form" method="post" action="<?= e(url('/cart/items/' . $albumId)) ?>">
                                        <?= \App\Core\Csrf::field() ?>
                                        <div>
                                            <label class="field-label" for="cart-qty-<?= $albumId ?>">Quantity</label>
                                            <input
                                                class="input cart-qty-input"
                                                type="number"
                                                id="cart-qty-<?= $albumId ?>"
                                                name="quantity"
                                                value="<?= $quantity ?>"
                                                min="1"
                                                max="<?= $maxQuantity ?>"
                                                inputmode="numeric"
                                                <?= $issue !== '' ? 'aria-describedby="' . e($issueId) . '"' : '' ?>>
                                        </div>
                                        <button class="btn btn--ghost btn--sm" type="submit">Update</button>
                                    </form>

                                    <form class="cart-remove-form" method="post" action="<?= e(url('/cart/items/' . $albumId . '/remove')) ?>">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button
                                            class="btn btn--ghost btn--sm cart-remove"
                                            type="submit"
                                            aria-label="Remove <?= e($name) ?> from cart">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary card card--cut" aria-label="Order summary">
                    <p class="eyebrow">Summary</p>
                    <dl class="cart-summary-list">
                        <div>
                            <dt>Subtotal</dt>
                            <dd><?= e(money($subtotal)) ?></dd>
                        </div>
                        <div>
                            <dt>Shipping</dt>
                            <dd>Estimated at checkout</dd>
                        </div>
                        <div class="cart-summary-total">
                            <dt>Total before shipping</dt>
                            <dd><?= e(money($subtotal)) ?></dd>
                        </div>
                    </dl>

                    <p class="cart-summary-note">
                        Ships from Seoul, South Korea. Shipping is a demonstrative estimate, not a courier quote.
                    </p>

                    <?php if ($hasIssues): ?>
                        <p class="cart-summary-warning" role="alert">
                            Remove unavailable items or reduce quantities before checkout.
                        </p>
                        <span class="btn btn--primary cart-checkout is-disabled" aria-disabled="true">Checkout</span>
                    <?php else: ?>
                        <a class="btn btn--primary cart-checkout" href="<?= e(url('/checkout')) ?>">Checkout</a>
                    <?php endif; ?>

                    <a class="cart-continue" href="<?= e(url('/discography')) ?>">Continue shopping</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
