<?php

$activePage = (string) ($active ?? '');
$navItems = [
    ['key' => 'home', 'label' => 'Home', 'page' => 'index', 'href' => url('/')],
    ['key' => 'discography', 'label' => 'Discography', 'page' => 'discography', 'href' => url('/discography')],
    ['key' => 'schedule', 'label' => 'Schedule', 'page' => 'schedule', 'href' => url('/schedule')],
    ['key' => 'news', 'label' => 'News', 'page' => 'news', 'href' => url('/news')],
    ['key' => 'about', 'label' => 'About', 'page' => 'about', 'href' => url('/about')],
    ['key' => 'gallery', 'label' => 'Gallery', 'page' => 'gallery', 'href' => url('/gallery')],
];
$cartCount = \App\Support\Cart::count();
$cartCountLabel = $cartCount > 99 ? '99+' : (string) $cartCount;
$cartActive = $activePage === 'cart';
?>
<nav class="navbar" id="dynamicNavbar" aria-label="Main navigation">
    <div class="navbar-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="NewJeans home">
            <span class="brand-logo" role="img" aria-label="NewJeans"></span>
        </a>
        <ul class="nav-links" id="navLinks">
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = $activePage === $item['key']; ?>
                <li>
                    <a
                        class="nav-link<?= $isActive ? ' is-active' : '' ?>"
                        href="<?= e($item['href']) ?>"
                        data-page="<?= e($item['page']) ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>
                    ><?= e($item['label']) ?></a>
                </li>
            <?php endforeach; ?>
            <li class="nav-cart-item">
                <a
                    class="nav-link nav-cart-link<?= $cartActive ? ' is-active' : '' ?>"
                    href="<?= e(url('/cart')) ?>"
                    data-page="cart"
                    aria-label="Cart, <?= e((string) $cartCount) ?> item<?= $cartCount === 1 ? '' : 's' ?>"
                    <?= $cartActive ? 'aria-current="page"' : '' ?>
                >
                    <span>Cart</span>
                    <span class="nav-cart-count" data-cart-count aria-hidden="true"><?= e($cartCountLabel) ?></span>
                </a>
            </li>
        </ul>
        <button
            class="nav-toggle"
            id="navToggle"
            type="button"
            aria-expanded="false"
            aria-controls="navLinks"
            aria-label="Toggle menu"
        >
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>
