<?php

$errors = is_array($errors ?? null) ? $errors : [];
$items = is_array($items ?? null) ? $items : [];
$countries = is_array($countries ?? null) ? $countries : [];
$cities = is_array($cities ?? null) ? $cities : [];
$shippingConfigured = (bool) ($shippingConfigured ?? false);
$destinationError = (bool) ($destinationError ?? false);
$quote = is_array($quote ?? null) ? $quote : null;
$subtotal = is_numeric($subtotal ?? null) ? (float) $subtotal : 0.0;
$estimatedTotal = is_numeric($estimatedTotal ?? null) ? (float) $estimatedTotal : $subtotal;
$nonce = is_string($nonce ?? null) ? $nonce : '';
$selectedCountry = is_string($selectedCountry ?? null) ? $selectedCountry : '';
$selectedCityId = (int) ($selectedCityId ?? 0);

$oldString = static function (string $key): string {
    $value = old($key, '');

    return is_scalar($value) ? (string) $value : '';
};

$nameValue = $oldString('recipient_name');
$phoneValue = $oldString('recipient_phone');
$addressValue = $oldString('shipping_address');
$shippingAmount = is_array($quote) && is_numeric($quote['shipping_usd'] ?? null)
    ? (float) $quote['shipping_usd']
    : null;
$distanceKm = is_array($quote) && is_numeric($quote['distance_km'] ?? null)
    ? (float) $quote['distance_km']
    : null;
?>
<section class="page-hero dot-blue checkout-hero">
    <div class="container">
        <p class="eyebrow">Guest checkout</p>
        <h1 class="page-title">Checkout</h1>
        <p class="page-lead">
            Add a recipient and destination, then review the simulated shipping estimate from Seoul.
        </p>
    </div>
</section>

<section class="section dot-paper checkout-section" aria-label="Checkout">
    <div class="container">
        <div class="checkout-layout">
            <form
                class="checkout-form card"
                method="post"
                action="<?= e(url('/checkout')) ?>"
                data-cities-url="<?= e(url('/shipping/cities')) ?>"
                data-estimate-url="<?= e(url('/shipping/estimate')) ?>"
                data-subtotal="<?= e(number_format($subtotal, 2, '.', '')) ?>"
                data-selected-country="<?= e($selectedCountry) ?>"
                data-selected-city="<?= $selectedCityId > 0 ? e((string) $selectedCityId) : '' ?>"
                novalidate>
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="checkout_nonce" value="<?= e($nonce) ?>">

                <?= \App\Core\View::render('partials/form-errors', ['errors' => array_values($errors)]) ?>

                <?php if (!$shippingConfigured): ?>
                    <p class="checkout-notice checkout-notice--warning" role="alert">
                        Checkout is disabled because shipping estimates are not configured. Please try again later.
                    </p>
                <?php endif; ?>

                <?php if ($destinationError): ?>
                    <p class="checkout-notice checkout-notice--warning" role="alert">
                        Shipping destination data is unavailable right now. Please try again later.
                    </p>
                <?php endif; ?>

                <fieldset class="checkout-fieldset">
                    <legend class="checkout-legend">Recipient</legend>

                    <div class="checkout-field">
                        <label class="field-label" for="recipientName">Recipient name</label>
                        <input
                            class="input<?= isset($errors['recipient_name']) ? ' input--invalid' : '' ?>"
                            type="text"
                            id="recipientName"
                            name="recipient_name"
                            value="<?= e($nameValue) ?>"
                            maxlength="120"
                            autocomplete="name"
                            required
                            <?= isset($errors['recipient_name']) ? 'aria-describedby="recipientName-error" aria-invalid="true"' : '' ?>>
                        <?php if (isset($errors['recipient_name'])): ?>
                            <p class="field-error" id="recipientName-error"><?= e($errors['recipient_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="checkout-field">
                        <label class="field-label" for="recipientPhone">Phone number</label>
                        <input
                            class="input<?= isset($errors['recipient_phone']) ? ' input--invalid' : '' ?>"
                            type="tel"
                            id="recipientPhone"
                            name="recipient_phone"
                            value="<?= e($phoneValue) ?>"
                            maxlength="30"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                            <?= isset($errors['recipient_phone']) ? 'aria-describedby="recipientPhone-error" aria-invalid="true"' : '' ?>>
                        <?php if (isset($errors['recipient_phone'])): ?>
                            <p class="field-error" id="recipientPhone-error"><?= e($errors['recipient_phone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="checkout-field">
                        <label class="field-label" for="shippingAddress">Street address</label>
                        <input
                            class="input<?= isset($errors['shipping_address']) ? ' input--invalid' : '' ?>"
                            type="text"
                            id="shippingAddress"
                            name="shipping_address"
                            value="<?= e($addressValue) ?>"
                            maxlength="255"
                            autocomplete="street-address"
                            required
                            <?= isset($errors['shipping_address']) ? 'aria-describedby="shippingAddress-error" aria-invalid="true"' : '' ?>>
                        <?php if (isset($errors['shipping_address'])): ?>
                            <p class="field-error" id="shippingAddress-error"><?= e($errors['shipping_address']) ?></p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <fieldset class="checkout-fieldset">
                    <legend class="checkout-legend">Destination</legend>

                    <div class="checkout-grid">
                        <div class="checkout-field">
                            <label class="field-label" for="destinationCountry">Country</label>
                            <select
                                class="select<?= isset($errors['country_code']) ? ' input--invalid' : '' ?>"
                                id="destinationCountry"
                                name="country_code"
                                autocomplete="country"
                                required
                                <?= isset($errors['country_code']) ? 'aria-describedby="destinationCountry-error" aria-invalid="true"' : '' ?>>
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                    <?php $countryCode = (string) ($country['code'] ?? ''); ?>
                                    <option value="<?= e($countryCode) ?>" <?= $countryCode === $selectedCountry ? 'selected' : '' ?>>
                                        <?= e($country['name'] ?? $countryCode) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['country_code'])): ?>
                                <p class="field-error" id="destinationCountry-error"><?= e($errors['country_code']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-field">
                            <label class="field-label" for="destinationCity">City</label>
                            <select
                                class="select<?= isset($errors['city_id']) ? ' input--invalid' : '' ?>"
                                id="destinationCity"
                                name="city_id"
                                aria-describedby="destinationCity-help<?= isset($errors['city_id']) ? ' destinationCity-error' : '' ?>"
                                <?= isset($errors['city_id']) ? 'aria-invalid="true"' : '' ?>>
                                <option value=""><?= $cities === [] ? 'Choose a country first' : 'Select a city' ?></option>
                                <?php foreach ($cities as $city): ?>
                                    <?php $cityId = (int) ($city['id'] ?? 0); ?>
                                    <?php $cityRegion = is_string($city['region'] ?? null) ? trim((string) $city['region']) : ''; ?>
                                    <option value="<?= $cityId ?>" <?= $cityId === $selectedCityId ? 'selected' : '' ?>>
                                        <?= e($city['name'] ?? '') ?><?= $cityRegion !== '' ? ' — ' . e($cityRegion) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-help" id="destinationCity-help">
                                City choices follow the selected country.
                            </p>
                            <?php if (isset($errors['city_id'])): ?>
                                <p class="field-error" id="destinationCity-error"><?= e($errors['city_id']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <noscript>
                        <p class="checkout-notice">
                            JavaScript is off, so the city list loads after the first submit. Choose a country,
                            submit once to load its cities, then pick a city.
                        </p>
                    </noscript>

                    <div class="checkout-estimate" aria-live="polite">
                        <p class="checkout-estimate-label">Estimated shipping from Seoul</p>
                        <?php if (!$shippingConfigured): ?>
                            <p class="checkout-estimate-value">Unavailable</p>
                        <?php else: ?>
                            <p class="checkout-estimate-value">
                                <span id="checkoutShippingAmount"><?= $shippingAmount !== null ? e(money($shippingAmount)) : 'Select a city to estimate' ?></span>
                                <span
                                    class="checkout-estimate-meta"
                                    id="checkoutDistanceMeta"
                                    <?= $distanceKm === null ? 'hidden' : '' ?>>
                                    <span id="checkoutDistance"><?= $distanceKm !== null ? e(number_format($distanceKm, 2)) : '' ?></span> km from Seoul
                                </span>
                            </p>
                            <p class="field-help" id="checkoutEstimateMessage" hidden></p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <div class="checkout-actions">
                    <a class="btn btn--ghost" href="<?= e(url('/cart')) ?>">Back to cart</a>
                    <?php if ($shippingConfigured && !$destinationError): ?>
                        <button class="btn btn--primary" type="submit">Place order</button>
                    <?php else: ?>
                        <span class="btn btn--primary is-disabled" aria-disabled="true">Checkout unavailable</span>
                    <?php endif; ?>
                </div>
            </form>

            <aside class="checkout-summary card card--cut" aria-label="Order summary">
                <p class="eyebrow">Order summary</p>

                <ul class="checkout-summary-items">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemId = (int) ($item['id'] ?? 0);
                        $itemName = (string) ($item['name'] ?? 'Album');
                        $itemCover = (string) ($item['cover'] ?? '');
                        $itemCoverSrc = $itemCover !== '' ? $itemCover : 'images/albums/cd.webp';
                        $itemPrice = (float) ($item['price'] ?? 0);
                        $itemQuantity = (int) ($item['quantity'] ?? 0);
                        $itemLineTotal = (float) ($item['line_total'] ?? 0);
                        ?>
                        <li class="checkout-summary-item">
                            <img
                                class="checkout-summary-cover"
                                src="<?= e(asset($itemCoverSrc)) ?>"
                                alt=""
                                loading="lazy">
                            <div class="checkout-summary-body">
                                <p class="checkout-summary-name"><?= e($itemName) ?></p>
                                <p class="checkout-summary-qty"><?= $itemQuantity ?> &times; <?= e(money($itemPrice)) ?></p>
                            </div>
                            <p class="checkout-summary-line"><?= e(money($itemLineTotal)) ?></p>
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
                        <dd id="checkoutShipping">
                            <?= $shippingAmount !== null ? e(money($shippingAmount)) : 'Select a destination' ?>
                        </dd>
                    </div>
                    <div class="cart-summary-total">
                        <dt>Estimated total</dt>
                        <dd id="checkoutTotal"><?= e(money($estimatedTotal)) ?></dd>
                    </div>
                </dl>

                <p class="cart-summary-note">
                    Ships from Seoul, South Korea. Shipping is a demonstrative estimate, not a courier quote.
                </p>

                <a class="cart-continue" href="<?= e(url('/cart')) ?>">Back to cart</a>
            </aside>
        </div>
    </div>
</section>
<script src="<?= e(asset('js/checkout.js')) ?>" defer></script>
