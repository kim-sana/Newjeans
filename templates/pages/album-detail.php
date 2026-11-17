<?php

$album = is_array($album ?? null) ? $album : [];
$songs = is_array($songs ?? null) ? $songs : [];
$related = is_array($related ?? null) ? $related : [];

$name = (string) ($album['name'] ?? 'Album');
$cover = (string) ($album['cover_album'] ?? '');
$typeName = (string) ($album['type_name'] ?? '');
$typeSlug = (string) ($album['type_slug'] ?? '');
$releaseDate = $album['release_date'] ?? null;
$releaseYear = format_date($releaseDate, 'Y');
$spotifyUrl = (string) ($album['spotify_url'] ?? '');
$youtubeUrl = (string) ($album['youtube_url'] ?? '');
$price = (float) ($album['price'] ?? 0);
$stock = (int) ($album['stock'] ?? 0);
$isForSale = (int) ($album['is_for_sale'] ?? 0) === 1;
$songCount = count($songs);
$badgeClass = $typeSlug === 'mini-album' ? 'mini' : $typeSlug;
$discImage = $cover !== '' ? $cover : 'images/albums/cd.webp';
$discAlt = $cover !== '' ? $name . ' album cover' : '';
?>
<section class="album-hero dot-blue" aria-labelledby="albumTitle">
    <div class="container album-hero-grid">
        <div class="album-hero-art">
            <div class="album-disc">
                <div class="album-disc-spin">
                    <img
                        class="album-disc-face"
                        src="<?= e(asset($discImage)) ?>"
                        alt="<?= e($discAlt) ?>"
                        loading="eager"
                        decoding="async">
                    <span class="album-disc-hole" aria-hidden="true"></span>
                </div>
                <span class="album-disc-shine" aria-hidden="true"></span>
                <?php if ($typeName !== ''): ?>
                    <span class="album-sticker album-sticker--<?= e($badgeClass) ?>"><?= e($typeName) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="album-hero-copy">
            <a class="album-back" href="<?= e(url('/discography')) ?>">&larr; Discography</a>
            <p class="eyebrow">Release archive</p>
            <h1 class="album-title" id="albumTitle"><?= e($name) ?></h1>

            <div class="album-meta">
                <span class="album-meta-item">
                    <?= $releaseDate !== null && $releaseDate !== '' ? e(format_date((string) $releaseDate, 'M j, Y')) : 'Release date TBA' ?>
                </span>
                <span class="album-meta-dot" aria-hidden="true"></span>
                <span class="album-meta-item"><?= $songCount ?> track<?= $songCount === 1 ? '' : 's' ?></span>
                <?php if ($typeName !== ''): ?>
                    <span class="album-meta-dot" aria-hidden="true"></span>
                    <span class="album-meta-item"><?= e($typeName) ?></span>
                <?php endif; ?>
            </div>

            <div class="album-hero-actions">
                <?php if ($spotifyUrl !== ''): ?>
                    <a class="btn btn--primary" href="<?= e($spotifyUrl) ?>" target="_blank" rel="noopener noreferrer">Spotify</a>
                <?php endif; ?>
                <?php if ($youtubeUrl !== ''): ?>
                    <a class="btn btn--outline-light" href="<?= e($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer">YouTube</a>
                <?php endif; ?>
                <?php if ($spotifyUrl === '' && $youtubeUrl === ''): ?>
                    <a class="btn btn--outline-light" href="<?= e(url('/discography')) ?>">Browse releases</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section album-detail-section dot-paper" aria-label="Album details">
    <div class="container album-detail-grid">
        <div class="album-tracks">
            <div class="album-sec-head">
                <p class="eyebrow">Tracklist</p>
                <h2>All tracks</h2>
                <p class="album-sec-lead">
                    <?= $songCount > 0 ? $songCount . ' song' . ($songCount === 1 ? '' : 's') . ' on this release.' : 'No tracks have been added yet.' ?>
                </p>
            </div>

            <?php if ($songs === []): ?>
                <div class="album-empty card card--cut">Tracklist coming soon.</div>
            <?php else: ?>
                <ol class="album-tracklist">
                    <?php foreach ($songs as $song): ?>
                        <li class="album-track">
                            <div class="album-track-body">
                                <span class="album-track-title"><?= e((string) ($song['title'] ?? '')) ?></span>
                                <?php if ((int) ($song['is_featured'] ?? 0) === 1): ?>
                                    <span class="album-track-flags">
                                        <span class="album-flag album-flag--featured">Featured</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <aside class="album-info-wrap" aria-label="Album information">
            <div class="album-info card card--cut">
                <p class="album-info-label">Album info</p>
                <dl class="album-info-list">
                    <div class="album-info-row">
                        <dt>Type</dt>
                        <dd><?= e($typeName !== '' ? $typeName : 'Release') ?></dd>
                    </div>
                    <div class="album-info-row">
                        <dt>Released</dt>
                        <dd><?= $releaseDate !== null && $releaseDate !== '' ? e(format_date((string) $releaseDate, 'M j, Y')) : 'TBA' ?></dd>
                    </div>
                    <div class="album-info-row">
                        <dt>Tracks</dt>
                        <dd><?= $songCount ?></dd>
                    </div>
                    <div class="album-info-row">
                        <dt>Status</dt>
                        <dd><?= $isForSale ? 'For sale' : 'Digital release' ?></dd>
                    </div>
                    <?php if ($isForSale): ?>
                        <div class="album-info-row">
                            <dt>Price</dt>
                            <dd><?= e(money($price)) ?></dd>
                        </div>
                        <div class="album-info-row">
                            <dt>Stock</dt>
                            <dd><?= $stock > 0 ? $stock . ' available' : 'Out of stock' ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <?php if ($releaseYear !== ''): ?>
                    <span class="sticker sticker--lime album-info-sticker"><?= e($releaseYear) ?></span>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <?php if ($related !== []): ?>
        <div class="container album-related">
            <div class="album-related-head">
                <div>
                    <p class="eyebrow">Keep listening</p>
                    <h2>More from <?= e($typeName !== '' ? $typeName : 'the archive') ?></h2>
                </div>
                <a class="album-related-all" href="<?= e(url('/discography')) ?>">All releases &rarr;</a>
            </div>

            <div class="album-related-grid">
                <?php foreach ($related as $item): ?>
                    <?php
                    $relatedId = (int) ($item['id'] ?? 0);
                    $relatedName = (string) ($item['name'] ?? '');
                    $relatedCover = (string) ($item['cover_album'] ?? '');
                    $relatedTypeSlug = (string) ($item['type_slug'] ?? '');
                    $relatedBadge = $relatedTypeSlug === 'mini-album' ? 'mini' : $relatedTypeSlug;
                    $relatedYear = format_date($item['release_date'] ?? null, 'Y');
                    ?>
                    <a class="album-related-card" href="<?= e(url('/discography/' . $relatedId)) ?>">
                        <?php if ($relatedCover !== ''): ?>
                            <img
                                class="album-related-cover"
                                src="<?= e(asset($relatedCover)) ?>"
                                alt="<?= e($relatedName) ?> album cover"
                                loading="lazy">
                        <?php endif; ?>
                        <span class="album-related-body">
                            <?php if (($item['type_name'] ?? '') !== ''): ?>
                                <span class="album-related-badge album-related-badge--<?= e($relatedBadge) ?>"><?= e((string) $item['type_name']) ?></span>
                            <?php endif; ?>
                            <span class="album-related-title"><?= e($relatedName) ?></span>
                            <span class="album-related-meta">
                                <?= $relatedYear !== '' ? e($relatedYear) : 'TBA' ?>
                                <span aria-hidden="true">&rarr;</span>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
