<?php

$story = is_array($story ?? null) ? $story : [];
$variant = (string) ($variant ?? 'list');
$cardIndex = (int) ($index ?? 0);

if ($story === []) {
    return;
}

$storySlug = (string) ($story['slug'] ?? '');
$storyTitle = (string) ($story['title'] ?? '');
$storyContent = (string) ($story['content'] ?? '');
$storyThumbnail = (string) ($story['thumbnail'] ?? '');
$storyUrl = url('/news/' . rawurlencode($storySlug));
$publishedIso = datetime_attr($story['published_at'] ?? null);
$publishedLabel = format_date($story['published_at'] ?? null, 'M j, Y');
$mediaUrl = $storyThumbnail !== '' ? asset($storyThumbnail) : '';
?>
<?php if ($variant === 'home'): ?>
    <article class="news-card">
        <a
            href="<?= e($storyUrl) ?>"
            aria-label="Read story: <?= e($storyTitle) ?>"
            style="display: block; color: inherit; text-decoration: none;"
        >
            <div class="news-card-media">
                <?php if ($mediaUrl !== ''): ?>
                    <img src="<?= e($mediaUrl) ?>" alt="<?= e($storyTitle) ?>" loading="lazy">
                <?php endif; ?>
            </div>
            <div class="news-card-body">
                <p class="news-card-headline"><?= e($storyTitle) ?></p>
                <time class="news-card-date" datetime="<?= e($publishedIso) ?>"><?= e($publishedLabel) ?></time>
            </div>
        </a>
    </article>
<?php elseif ($variant === 'featured'): ?>
    <article class="news-featured">
        <a class="news-featured-link" href="<?= e($storyUrl) ?>" aria-label="Read top story: <?= e($storyTitle) ?>">
            <div class="news-featured-media">
                <?php if ($mediaUrl !== ''): ?>
                    <img src="<?= e($mediaUrl) ?>" alt="<?= e($storyTitle) ?>" loading="eager" decoding="async">
                <?php endif; ?>
            </div>
            <div class="news-featured-body">
                <span class="news-featured-badge">Top story</span>
                <h3 class="news-featured-title"><?= e($storyTitle) ?></h3>
                <p class="news-featured-excerpt"><?= e(excerpt($storyContent, 210)) ?></p>
                <div class="news-featured-meta">
                    <time class="news-featured-date" datetime="<?= e($publishedIso) ?>"><?= e($publishedLabel) ?></time>
                    <span class="news-featured-cta">Read story</span>
                </div>
            </div>
        </a>
    </article>
<?php else: ?>
    <article class="news-list-card" style="animation-delay: <?= min($cardIndex, 6) * 45 ?>ms">
        <a class="news-card-link" href="<?= e($storyUrl) ?>" aria-label="Read story: <?= e($storyTitle) ?>">
            <div class="news-card-media">
                <?php if ($mediaUrl !== ''): ?>
                    <img src="<?= e($mediaUrl) ?>" alt="<?= e($storyTitle) ?>" loading="lazy" decoding="async">
                <?php endif; ?>
            </div>
            <div class="news-card-body">
                <time class="news-card-date" datetime="<?= e($publishedIso) ?>"><?= e($publishedLabel) ?></time>
                <h3 class="news-card-title"><?= e($storyTitle) ?></h3>
                <p class="news-card-excerpt"><?= e(excerpt($storyContent, 140)) ?></p>
                <span class="news-card-cta">Read story</span>
            </div>
        </a>
    </article>
<?php endif; ?>
