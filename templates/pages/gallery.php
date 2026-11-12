<?php

$items = is_array($items ?? null) ? $items : [];
$itemCount = count($items);
?>
<section class="section gallery-section" id="gallery">
    <div class="container">
        <div class="sec-head sec-head--center reveal">
            <div>
                <h1>Gallery</h1>
                <p class="sec-lead">
                    A rotating look at stages, concept shoots and candid moments
                    from the Bunnies universe.
                </p>
            </div>
        </div>

        <?php if ($items === []): ?>
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
                        <?php foreach ($items as $index => $item): ?>
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
                                    aria-label="Open gallery photo <?= $index + 1 ?> of <?= $itemCount ?>">
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

            <div class="masonry-wrapper" id="masonryWrapper" inert>
                <div class="masonry-grid" id="galleryGrid">
                    <?php foreach ($items as $index => $item): ?>
                        <?php
                        $image = (string) ($item['image'] ?? '');
                        $alt = (string) ($item['alt_text'] ?? 'NewJeans gallery photo ' . ($index + 1));
                        $imageUrl = $image !== '' ? asset($image) : '';
                        ?>
                        <?php if ($imageUrl !== ''): ?>
                            <button
                                class="masonry-item"
                                type="button"
                                data-gallery-index="<?= $index + 1 ?>"
                                data-gallery-src="<?= e($imageUrl) ?>"
                                aria-label="Open gallery photo <?= $index + 1 ?> of <?= $itemCount ?>">
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($alt) ?>" loading="lazy" decoding="async">
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="gallery-actions">
                <button
                    id="expandGalleryBtn"
                    class="btn btn--ghost"
                    type="button"
                    aria-expanded="false"
                    aria-controls="masonryWrapper">
                    View full gallery
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<div
    id="imageModal"
    class="image-modal"
    role="dialog"
    aria-modal="true"
    aria-label="Gallery viewer"
    aria-hidden="true">
    <span class="close-modal" id="closeModal" role="button" tabindex="0" aria-label="Close gallery">&times;</span>
    <button class="modal-nav-btn prev-btn" id="prevModalBtn" type="button" aria-label="Previous image">&lt;</button>
    <img class="modal-content" id="modalImg" alt="Gallery image enlarged">
    <button class="modal-nav-btn next-btn" id="nextModalBtn" type="button" aria-label="Next image">&gt;</button>
</div>
