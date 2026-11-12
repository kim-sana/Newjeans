<?php

use App\Core\View;

$story = is_array($story ?? null) ? $story : [];
$related = is_array($related ?? null) ? $related : [];
$title = (string) ($story['title'] ?? '');
$content = (string) ($story['content'] ?? '');
$thumbnail = (string) ($story['thumbnail'] ?? '');
$publishedAt = $story['published_at'] ?? null;
$blocks = paragraphs($content);
$readMinutes = reading_time($content);
?>
<section class="section news-page-main dot-paper" aria-label="News article">
    <div class="container">
        <div id="newsContent" class="news-content" aria-live="polite" aria-busy="false">
            <article class="news-article">
                <a href="<?= e(url('/news')) ?>" class="news-back-link">← All stories</a>
                <p class="eyebrow news-article-kicker">News story</p>
                <h2 class="news-article-title"><?= e($title) ?></h2>
                <div class="news-article-meta">
                    <time class="news-article-date" datetime="<?= e(datetime_attr($publishedAt)) ?>"><?= e(format_date($publishedAt, 'M j, Y')) ?></time>
                    <span class="news-article-meta-dot" aria-hidden="true"></span>
                    <span><?= $readMinutes ?> min read</span>
                </div>

                <?php if ($thumbnail !== ''): ?>
                    <div class="news-article-media">
                        <img src="<?= e(asset($thumbnail)) ?>" alt="<?= e($title) ?>" loading="eager" decoding="async">
                    </div>
                <?php endif; ?>

                <div class="news-article-content">
                    <?php if ($blocks === []): ?>
                        <p class="news-article-empty-copy">Full article coming soon.</p>
                    <?php else: ?>
                        <?php foreach ($blocks as $block): ?>
                            <p><?= e($block) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="news-article-footer">
                    <a href="<?= e(url('/news')) ?>" class="news-back-link">← Back to all stories</a>
                </div>
            </article>

            <?php if ($related !== []): ?>
                <section class="news-related">
                    <div class="news-related-head">
                        <h2>More stories</h2>
                    </div>
                    <div class="news-list-grid">
                        <?php foreach ($related as $index => $relatedStory): ?>
                            <?= View::render('partials/news-card', ['story' => $relatedStory, 'variant' => 'list', 'index' => $index]) ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
