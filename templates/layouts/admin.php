<?php

$pageTitle = $title ?? 'Admin';
$adminUser = $user ?? null;
$activeMenu = $active ?? '';
$flashError = flash('error');
$flashSuccess = flash('success');
$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer', 'url' => url('/admin')],
    ['key' => 'album-types', 'label' => 'Album Types', 'icon' => 'bi-tags', 'url' => url('/admin/album-types')],
    ['key' => 'albums', 'label' => 'Albums', 'icon' => 'bi-vinyl', 'url' => url('/admin/albums')],
    ['key' => 'news', 'label' => 'News', 'icon' => 'bi-newspaper', 'url' => url('/admin/news')],
    ['key' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event', 'url' => url('/admin/events')],
    ['key' => 'members', 'label' => 'Members', 'icon' => 'bi-people', 'url' => url('/admin/members')],
    ['key' => 'histories', 'label' => 'Histories', 'icon' => 'bi-clock-history', 'url' => url('/admin/histories')],
    ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'bi-images', 'url' => url('/admin/gallery')],
    ['key' => 'orders', 'label' => 'Orders', 'icon' => 'bi-receipt', 'url' => url('/admin/orders')],
];
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="dark">
    <title><?= e($pageTitle) ?> &middot; NewJeans Admin</title>
    <link rel="icon" href="<?= e(asset('images/brand/favicon.png')) ?>">

    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/source-sans-3.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/overlayscrollbars.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/adminlte.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="<?= e(url('/')) ?>" class="nav-link" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                        View site
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (is_array($adminUser)): ?>
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="d-none d-md-inline"><?= e($adminUser['name'] ?? 'Admin') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <li class="user-header text-bg-primary">
                                <i class="bi bi-person-circle display-4" aria-hidden="true"></i>
                                <p>
                                    <?= e($adminUser['name'] ?? 'Admin') ?>
                                    <small><?= e($adminUser['email'] ?? '') ?></small>
                                </p>
                            </li>
                            <li class="user-footer">
                                <a href="<?= e(url('/')) ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                    View site
                                </a>
                                <form method="post" action="<?= e(url('/logout')) ?>" class="float-end">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-outline-danger">Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= e(url('/admin')) ?>" class="brand-link">
                <span class="brand-text fw-light">NewJeans Admin</span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2" aria-label="Main navigation">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" role="menu">
                    <?php foreach ($navItems as $item): ?>
                        <li class="nav-item">
                            <a href="<?= e($item['url']) ?>" class="nav-link<?= $activeMenu === $item['key'] ? ' active' : '' ?>">
                                <i class="nav-icon bi <?= e($item['icon']) ?>"></i>
                                <p><?= e($item['label']) ?></p>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h1 class="mb-0 fs-3"><?= e($pageTitle) ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="<?= e(url('/admin')) ?>">Admin</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle) ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($flashSuccess) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (is_string($flashError) && $flashError !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= e($flashError) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">NewJeans Fan Hub</div>
        <strong>&copy; <?= date('Y') ?> NewJeans Fan Hub.</strong> Admin panel.
    </footer>
</div>

<script src="<?= e(asset('vendor/adminlte-v4/js/overlayscrollbars.browser.es6.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/popper.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/bootstrap.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/adminlte.js')) ?>"></script>
<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
    };

    document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        const isMobile = window.innerWidth <= 992;

        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined && !isMobile) {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                },
            });
        }
    });
</script>
</body>
</html>
