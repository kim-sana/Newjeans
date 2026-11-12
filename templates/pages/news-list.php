<?php

use App\Core\View;

$stories = is_array($stories ?? null) ? $stories : [];
$featured = is_array($featured ?? null) ? $featured : null;
$rest = is_array($rest ?? null) ? $rest : [];
$storyCount = count($stories);
?>
<section class="page-hero dot-blue news-hero">
    <div class="container">
        <div class="news-hero-chibis" aria-hidden="true">
            <img class="news-hero-chibi news-hero-chibi--hanni" src="<?= e(asset('images/misc/hanni-chibi.png')) ?>" alt="">
            <img class="news-hero-chibi news-hero-chibi--hyein" src="<?= e(asset('images/misc/hyein-chibi.png')) ?>" alt="">
        </div>
        <h1 class="page-title">Highlight News</h1>
        <p class="page-lead">
            Chart milestones, award nights, stages and cover stories from the
            Bunnies universe.
        </p>
    </div>
</section>

<section class="section news-page-main dot-paper" aria-label="News">
    <div class="container">
        <div class="news-view-head sec-head reveal" id="newsViewHead">
            <div>
                <p class="eyebrow">News archive</p>
                <h2 id="newsViewTitle">All stories</h2>
                <p class="sec-lead">A fan-made archive of verified NewJeans moments.</p>
            </div>
            <span class="news-count" id="newsCount" aria-live="polite">
                <?php if ($storyCount > 0): ?><?= $storyCount ?> stor<?= $storyCount === 1 ? 'y' : 'ies' ?><?php endif; ?>
            </span>
        </div>

        <div id="newsContent" class="news-content" aria-live="polite" aria-busy="false">
            <?php if ($featured === null): ?>
                <section class="news-state" role="status">
                    <h3>No published highlights yet</h3>
                    <p>Check back when the next story is published.</p>
                </section>
            <?php else: ?>
                <?= View::render('partials/news-card', ['story' => $featured, 'variant' => 'featured']) ?>

                <?php if ($rest !== []): ?>
                    <div class="news-list-grid">
                        <?php foreach ($rest as $index => $story): ?>
                            <?= View::render('partials/news-card', ['story' => $story, 'variant' => 'list', 'index' => $index]) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
