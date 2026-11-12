<?php

use App\Core\View;

$pageTitle = (string) ($title ?? 'NewJeans');
$pageDescription = (string) ($description ?? '');
$activePage = (string) ($active ?? '');
$pageStyles = is_array($styles ?? null) ? $styles : [];
$pageScripts = is_array($scripts ?? null) ? $scripts : [];
$pageBodyClass = (string) ($bodyClass ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($pageDescription !== ''): ?>
        <meta name="description" content="<?= e($pageDescription) ?>">
    <?php endif; ?>
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,400;1,700;1,800;1,900&family=Bagel+Fat+One&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= e(asset('images/brand/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/locomotive-scroll/locomotive-scroll.css')) ?>">
    <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?= e(asset('css/' . $style)) ?>">
    <?php endforeach; ?>
</head>
<body<?= $pageBodyClass !== '' ? ' class="' . e($pageBodyClass) . '"' : '' ?>>
<a class="skip-link" href="#main">Skip to content</a>

<?= View::render('partials/header', ['active' => $activePage]) ?>
<?= View::render('partials/flash') ?>

<main id="main">
    <?= $content ?>
</main>

<?= View::render('partials/footer') ?>

<script src="<?= e(asset('vendor/locomotive-scroll/locomotive-scroll.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/theme.js')) ?>" defer></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
