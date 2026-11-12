<section class="section dot-paper">
    <div class="container" style="padding: 4rem 0; text-align: center;">
        <p class="eyebrow">Page not found</p>
        <h1 class="page-title">404</h1>
        <p class="page-lead"><?= e($message ?? 'The page you are looking for does not exist.') ?></p>
        <p style="margin-top: 1.5rem;">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>">Back to home</a>
            <a class="btn btn--ghost" href="<?= e(url('/news')) ?>">Browse news</a>
        </p>
    </div>
</section>
