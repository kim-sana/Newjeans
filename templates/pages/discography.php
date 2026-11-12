<?php

$releases = is_array($releases ?? null) ? $releases : [];
$albumTypes = is_array($albumTypes ?? null) ? $albumTypes : [];
?>
<section class="page-hero disco-hero dot-blue">
    <div class="container disco-hero-grid">
        <div class="disco-hero-copy">
            <h1 class="page-title">Discography</h1>
            <p class="page-lead">
                From a mystery debut to global stages — every release, single and
                collaboration gathered in one blue thread.
            </p>
        </div>
        <div class="disco-hero-art">
            <img class="disco-hero-chibi" src="<?= e(asset('images/misc/haerin-chibi.png')) ?>" alt="Haerin chibi">
            <div class="disco-hero-cd-float">
                <img class="disco-hero-cd" src="<?= e(asset('images/albums/cd.webp')) ?>" alt="">
            </div>
        </div>
    </div>
</section>

<section id="discography" class="section disco-section">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <p class="eyebrow">Release archive</p>
                <h2>Music Discography</h2>
                <p class="sec-lead">Search, filter and sort every era of NewJeans.</p>
            </div>
            <span class="brand-logo disco-head-logo" role="img" aria-label="NewJeans"></span>
        </div>

        <div class="disco-filter-bar reveal">
            <div class="disco-search">
                <svg class="disco-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <label class="sr-only" for="searchInput">Search release title</label>
                <input class="input" type="text" id="searchInput" placeholder="Search release title..." autocomplete="off">
            </div>

            <div class="disco-filter">
                <span class="disco-filter-label" id="categoryLabel">Category</span>
                <div class="disco-select" id="categorySelect">
                    <button
                        class="disco-select-trigger"
                        id="categoryTrigger"
                        type="button"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="categoryMenu"
                        aria-labelledby="categoryLabel categoryValue">
                        <span class="disco-select-value" id="categoryValue">All categories</span>
                        <svg class="disco-select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <ul class="disco-select-menu" id="categoryMenu" role="listbox" aria-labelledby="categoryLabel" hidden>
                        <li class="disco-select-option" role="option" tabindex="-1" data-value="all" aria-selected="true">All categories</li>
                        <?php foreach ($albumTypes as $type): ?>
                            <li
                                class="disco-select-option"
                                role="option"
                                tabindex="-1"
                                data-value="<?= e($type['slug'] ?? '') ?>"
                                aria-selected="false"><?= e($type['name'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="disco-filter">
                <span class="disco-filter-label" id="sortLabel">Sort by</span>
                <div class="disco-select" id="sortSelect">
                    <button
                        class="disco-select-trigger"
                        id="sortTrigger"
                        type="button"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="sortMenu"
                        aria-labelledby="sortLabel sortValue">
                        <span class="disco-select-value" id="sortValue">Newest &rarr; Oldest</span>
                        <svg class="disco-select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <ul class="disco-select-menu" id="sortMenu" role="listbox" aria-labelledby="sortLabel" hidden>
                        <li class="disco-select-option" role="option" tabindex="-1" data-value="newest" aria-selected="true">Newest &rarr; Oldest</li>
                        <li class="disco-select-option" role="option" tabindex="-1" data-value="oldest" aria-selected="false">Oldest &rarr; Newest</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="disco-bottom-row reveal">
            <div class="disco-chips" role="group" aria-label="Filter by release type">
                <div class="disco-chip-group">
                    <button class="chip is-active" type="button" data-filter="all">All releases</button>
                    <?php foreach ($albumTypes as $type): ?>
                        <button class="chip" type="button" data-filter="<?= e($type['slug'] ?? '') ?>"><?= e($type['name'] ?? '') ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="control-actions">
                <button class="btn btn--ghost btn--sm" id="resetBtn" type="button">Reset filters</button>
                <button class="btn btn--primary btn--sm" id="applyBtn" type="button">Apply search</button>
            </div>
        </div>

        <div class="release-list" id="releaseList">
            <?php if ($releases === []): ?>
                <p class="sec-lead">No releases have been published yet.</p>
            <?php endif; ?>
            <?php foreach ($releases as $release): ?>
                <?php
                $typeSlug = (string) ($release['type_slug'] ?? '');
                $badgeClass = $typeSlug === 'mini-album' ? 'mini' : $typeSlug;
                $releaseDate = (string) ($release['release_date'] ?? '');
                $releaseYear = format_date($releaseDate, 'Y');
                $cover = (string) ($release['cover_album'] ?? '');
                $spotifyUrl = (string) ($release['spotify_url'] ?? '');
                $youtubeUrl = (string) ($release['youtube_url'] ?? '');
                $albumSongs = is_array($release['songs'] ?? null) ? $release['songs'] : [];
                ?>
                <article class="release-row" data-type="<?= e($typeSlug) ?>" data-year="<?= e($releaseYear) ?>">
                    <div class="rr-cover-wrap">
                        <?php if ($cover !== ''): ?>
                            <img
                                src="<?= e(asset($cover)) ?>"
                                alt="<?= e((string) ($release['name'] ?? '')) ?> album cover"
                                class="rr-cover"
                                loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="rr-body">
                        <div class="rr-header">
                            <h3 class="rr-title"><?= e($release['name'] ?? '') ?></h3>
                            <span class="rr-badge <?= e($badgeClass) ?>"><?= e($release['type_name'] ?? '') ?></span>
                        </div>
                        <p class="rr-date">
                            <?= $releaseDate !== '' ? 'Released: ' . e(format_date($releaseDate, 'M d, Y')) : 'Release date TBA' ?>
                        </p>
                        <ul class="rr-tracks">
                            <?php foreach ($albumSongs as $song): ?>
                                <li>
                                    <span class="ti"><?= e(sprintf('%02d', (int) ($song['track_number'] ?? 0))) ?></span>
                                    <?= e($song['title'] ?? '') ?>
                                    <?php if ((int) ($song['is_title'] ?? 0) === 1): ?>
                                        <span class="title-flag">Title</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="rr-actions">
                        <?php if ($spotifyUrl !== ''): ?>
                            <a href="<?= e($spotifyUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--primary btn--sm">Spotify</a>
                        <?php endif; ?>
                        <?php if ($youtubeUrl !== ''): ?>
                            <a href="<?= e($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--ghost btn--sm">YouTube</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
