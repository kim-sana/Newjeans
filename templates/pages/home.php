<?php

use App\Core\View;

$featuredSongs = is_array($featuredSongs ?? null) ? $featuredSongs : [];
$galleryItems = is_array($galleryItems ?? null) ? $galleryItems : [];
$newsItems = is_array($newsItems ?? null) ? $newsItems : [];
$trackPositions = ['pos-active', 'pos-next', 'pos-prev'];
?>
<section class="hero dot-blue" id="hero" aria-labelledby="hero-title">
    <div class="container hero-grid">
        <div class="hero-copy">
            <img
                class="hero-avatar hero-avatar--minji"
                src="<?= e(asset('images/misc/minji-chibi.png')) ?>"
                alt="Minji chibi">
            <span
                class="brand-logo brand-logo--white hero-logo"
                role="img"
                aria-label="NewJeans"></span>
            <h1 class="hero-title" id="hero-title">The new era<br>of pop.</h1>
            <p class="hero-lead">
                A casual, "girl-next-door" image heavily inspired by 1990s and
                2000s Y2K pop and R&amp;B styles.
            </p>
            <div class="hero-actions">
                <a href="<?= e(url('/discography')) ?>" class="btn btn--primary btn--on-blue">Listen now</a>
                <a href="<?= e(url('/schedule')) ?>" class="btn btn--outline-light">See schedule</a>
            </div>
            <div class="hero-stickers">
                <img
                    class="hero-avatar hero-avatar--hanni"
                    src="<?= e(asset('images/misc/hanni-chibi.png')) ?>"
                    alt="Hanni chibi">
                <img
                    class="hero-avatar hero-avatar--haerin"
                    src="<?= e(asset('images/misc/haerin-chibi.png')) ?>"
                    alt="Haerin chibi">
            </div>
        </div>

        <div class="hero-media">
            <figure class="video-card card--cut">
                <video
                    autoplay
                    loop
                    muted
                    playsinline
                    preload="metadata"
                    poster="<?= e(asset('images/albums/get-up.jpg')) ?>"
                    aria-label="NewJeans music video preview">
                    <source src="<?= e(asset('videos/mv.mp4')) ?>" type="video/mp4">
                </video>
                <figcaption>
                    <span>Supernatural · Official MV</span>
                    <span class="video-card-dot" aria-hidden="true"></span>
                </figcaption>
            </figure>
            <img
                class="hero-avatar hero-sticker hero-sticker--one hero-avatar--hyein"
                src="<?= e(asset('images/misc/hyein-chibi.png')) ?>"
                alt="Hyein chibi">
            <span class="sticker sticker--lime hero-sticker hero-sticker--two" aria-hidden="true">NewJeans Never Die</span>
        </div>
    </div>

    <div class="marquee" aria-hidden="true">
        <div class="marquee-track">
            <span>ATTENTION * DITTO * HYPE BOY * OMG * SUPER SHY * ETA * HOW SWEET * SUPERNATURAL * BUBBLE GUM *</span>
            <span>ATTENTION * DITTO * HYPE BOY * OMG * SUPER SHY * ETA * HOW SWEET * SUPERNATURAL * BUBBLE GUM *</span>
        </div>
    </div>
</section>

<section class="section tracks-section dot-paper" id="track-session">
    <div class="container">
        <div class="sec-head sec-head--center reveal">
            <div>
                <h2>Top 3 Songs</h2>
                <p class="sec-lead">
                    Three iconic masterpieces that dominated global charts and
                    captured the hearts of millions of Bunnies worldwide.
                </p>
            </div>
        </div>

        <?php if ($featuredSongs === []): ?>
            <p class="sec-lead">Featured songs will appear here once they are marked in the admin panel.</p>
        <?php else: ?>
            <div class="carousel-wrap">
                <div class="carousel-track" id="trackCarousel3D">
                    <?php foreach ($featuredSongs as $index => $song): ?>
                        <?php
                        $songTitle = (string) ($song['title'] ?? '');
                        $albumName = (string) ($song['album_name'] ?? '');
                        $cover = (string) ($song['cover_album'] ?? '');
                        $releaseYear = format_date($song['release_date'] ?? null, 'Y');
                        $spotifyUrl = (string) ($song['spotify_url'] ?? '');
                        $youtubeUrl = (string) ($song['youtube_url'] ?? '');
                        $trackLabel = $albumName === 'NewJeans (Debut)'
                            ? 'Debut mini album'
                            : (string) ($song['type_name'] ?? '');
                        ?>
                        <article class="track-card <?= e($trackPositions[$index % 3] ?? 'pos-hidden') ?>">
                            <div class="track-art-wrap">
                                <div class="track-cd-wrap">
                                    <img src="<?= e(asset('images/albums/cd.webp')) ?>" alt="" class="track-cd-spin">
                                </div>
                                <?php if ($cover !== ''): ?>
                                    <img src="<?= e(asset($cover)) ?>" alt="<?= e($songTitle) ?> cover" class="track-art">
                                <?php endif; ?>
                                <span class="track-number"><?= e(sprintf('%02d', $index + 1)) ?></span>
                            </div>
                            <div class="track-body">
                                <p class="track-label"><?= e($trackLabel) ?></p>
                                <h3 class="track-name"><?= e($songTitle) ?></h3>
                                <p class="track-info"><?= e($albumName) ?> &bull; <?= e($releaseYear) ?></p>
                                <div class="track-links">
                                    <?php if ($spotifyUrl !== ''): ?>
                                        <a href="<?= e($spotifyUrl) ?>" target="_blank" rel="noopener noreferrer" class="plat">Spotify</a>
                                    <?php endif; ?>
                                    <?php if ($youtubeUrl !== ''): ?>
                                        <a href="<?= e($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="plat">YouTube</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="carousel-controls">
                    <button id="btnPrev3D" class="carousel-btn" type="button" aria-label="Previous track">&lt;</button>
                    <div class="carousel-dots">
                        <?php foreach ($featuredSongs as $index => $song): ?>
                            <button
                                class="cdot<?= $index === 0 ? ' active' : '' ?>"
                                type="button"
                                data-index="<?= $index ?>"
                                aria-label="Show track <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <button id="btnNext3D" class="carousel-btn" type="button" aria-label="Next track">&gt;</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="tracks-actions">
            <a href="<?= e(url('/discography')) ?>" class="btn">Explore all songs</a>
        </div>
    </div>
</section>

<section id="gallery" class="section gallery-section">
    <div class="container">
        <div class="sec-head sec-head--center reveal">
            <div>
                <h2>Gallery</h2>
                <p class="sec-lead">
                    A rotating look at stages, concept shoots and candid moments
                    from the Bunnies universe.
                </p>
            </div>
        </div>

        <?php if ($galleryItems === []): ?>
            <p class="sec-lead">Gallery photos will appear here once they are added.</p>
        <?php else: ?>
            <div
                class="coverflow"
                id="coverflow"
                role="region"
                aria-roledescription="carousel"
                aria-label="NewJeans gallery photos">
                <div class="coverflow-stage" id="coverflowStage" tabindex="0">
                    <div class="coverflow-track" id="coverflowTrack">
                        <?php foreach ($galleryItems as $index => $item): ?>
                            <?php
                            $image = (string) ($item['image'] ?? '');
                            $alt = (string) ($item['alt_text'] ?? 'NewJeans gallery photo ' . ($index + 1));
                            $imageUrl = $image !== '' ? asset($image) : '';
                            ?>
                            <?php if ($imageUrl !== ''): ?>
                                <button
                                    class="coverflow-card"
                                    type="button"
                                    data-gallery-index="<?= $index + 1 ?>"
                                    data-gallery-src="<?= e($imageUrl) ?>"
                                    aria-label="Open gallery photo <?= $index + 1 ?> of <?= count($galleryItems) ?>">
                                    <img
                                        src="<?= e($imageUrl) ?>"
                                        alt="<?= e($alt) ?>"
                                        loading="eager"
                                        decoding="async"
                                        draggable="false">
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="sr-only" id="slideAnnouncement" aria-live="polite"></p>
                </div>

                <div class="coverflow-controls">
                    <button class="coverflow-arrow" id="previousSlide" type="button" aria-label="Previous photo">
                        <span aria-hidden="true">←</span>
                    </button>
                    <div class="coverflow-pagination" id="slidePagination" role="group" aria-label="Carousel pagination">
                        <button class="pagination-dot is-active" type="button" data-page="0" aria-label="Go to pagination dot 1" aria-current="page"></button>
                        <button class="pagination-dot" type="button" data-page="1" aria-label="Go to pagination dot 2"></button>
                        <button class="pagination-dot" type="button" data-page="2" aria-label="Go to pagination dot 3"></button>
                    </div>
                    <button class="coverflow-arrow" id="nextSlide" type="button" aria-label="Next photo">
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="gallery-actions">
            <a href="<?= e(url('/gallery')) ?>?expanded=1" class="btn btn--ghost">View full gallery</a>
        </div>
    </div>
</section>

<section id="news" class="section news-section dot-paper">
    <div class="container news-inner">
        <div class="sec-head reveal">
            <div>
                <p class="eyebrow">From the archive</p>
                <h2>Highlight News</h2>
            </div>
            <a href="<?= e(url('/news')) ?>" class="news-see-all">See all news</a>
        </div>
    </div>

    <div class="news-track-outer">
        <div class="news-track" id="newsTrack">
            <?php if ($newsItems === []): ?>
                <p class="news-card-body">No published highlights yet.</p>
            <?php else: ?>
                <?php for ($pass = 0; $pass < 2; $pass++): ?>
                    <?php foreach ($newsItems as $story): ?>
                        <?= View::render('partials/news-card', ['story' => $story, 'variant' => 'home']) ?>
                    <?php endforeach; ?>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<div
    id="imageModal"
    class="image-modal"
    role="dialog"
    aria-modal="true"
    aria-label="Gallery viewer">
    <span class="close-modal" id="closeModal" role="button" tabindex="0" aria-label="Close gallery">&times;</span>
    <button class="modal-nav-btn prev-btn" id="prevModalBtn" type="button" aria-label="Previous image">&lt;</button>
    <img class="modal-content" id="modalImg" alt="Gallery image enlarged">
    <button class="modal-nav-btn next-btn" id="nextModalBtn" type="button" aria-label="Next image">&gt;</button>
</div>
