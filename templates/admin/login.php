<?php

$pageTitle = $title ?? 'Admin Sign In';
$emailValue = $email ?? '';
$formErrors = is_array($errors ?? null) ? $errors : [];
$flashError = flash('error');
$flashSuccess = flash('success');
?>
<!doctype html>
<html lang="en" data-bs-theme="light" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light">
    <title><?= e($pageTitle) ?> &middot; NewJeans Admin</title>
    <link rel="icon" href="<?= e(asset('images/brand/favicon.png')) ?>">

    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/source-sans-3.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/overlayscrollbars.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/adminlte-v4/css/adminlte.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-page bg-body-secondary">
<main class="login-box">
    <h1 class="login-logo">
        <a href="<?= e(url('/')) ?>"><b>NewJeans</b> Admin</a>
    </h1>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to manage site content</p>

            <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                <div class="alert alert-success" role="alert"><?= e($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (is_string($flashError) && $flashError !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?php if ($formErrors !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($formErrors as $message): ?>
                            <li><?= e($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>" novalidate>
                <?= \App\Core\Csrf::field() ?>

                <label class="visually-hidden" for="loginEmail">Email</label>
                <div class="input-group mb-3">
                    <input
                        id="loginEmail"
                        type="email"
                        name="email"
                        class="form-control<?= isset($formErrors['email']) ? ' is-invalid' : '' ?>"
                        value="<?= e($emailValue) ?>"
                        placeholder="Email"
                        autocomplete="username"
                        required
                        autofocus
                    >
                    <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                </div>

                <label class="visually-hidden" for="loginPassword">Password</label>
                <div class="input-group mb-3">
                    <input
                        id="loginPassword"
                        type="password"
                        name="password"
                        class="form-control<?= isset($formErrors['password']) ? ' is-invalid' : '' ?>"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >
                    <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Sign In</button>
                        </div>
                    </div>
                </div>
            </form>

            <p class="mt-3 mb-0 text-center">
                <a href="<?= e(url('/')) ?>">Back to public site</a>
            </p>
        </div>
    </div>
</main>

<script src="<?= e(asset('vendor/adminlte-v4/js/overlayscrollbars.browser.es6.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/popper.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/bootstrap.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/adminlte-v4/js/adminlte.js')) ?>"></script>
</body>
</html>
