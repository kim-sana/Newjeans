<?php

$event = is_array($event ?? null) ? $event : [];
$cardIndex = (int) ($index ?? 0);

if ($event === []) {
    return;
}

$eventSlug = (string) ($event['slug'] ?? '');
$eventTitle = (string) ($event['title'] ?? '');
$eventVenue = (string) ($event['venue'] ?? '');
$eventCity = (string) ($event['city'] ?? '');
$eventDescription = (string) ($event['description'] ?? '');
$eventPoster = (string) ($event['poster'] ?? '');
$eventDate = (string) ($event['event_date'] ?? '');
$timestamp = $eventDate !== '' ? strtotime($eventDate) : false;

$dateIso = $timestamp === false ? '' : date('Y-m-d', $timestamp);
$dateLabel = $timestamp === false ? '' : date('M j, Y', $timestamp);
$monthLabel = $timestamp === false ? '' : strtoupper(date('M', $timestamp));
$dayLabel = $timestamp === false ? '' : date('j', $timestamp);
$yearLabel = $timestamp === false ? '' : date('Y', $timestamp);
$timeLabel = $timestamp === false ? '' : date('H:i', $timestamp);
?>
<article
    class="wf-card"
    id="<?= e($eventSlug) ?>"
    data-slug="<?= e($eventSlug) ?>"
    data-date="<?= e($dateIso) ?>"
    data-month="<?= e($monthLabel) ?>"
    data-title="<?= e($eventTitle) ?>"
    data-venue="<?= e($eventVenue) ?>"
    data-city="<?= e($eventCity) ?>"
    data-time="<?= e($timeLabel) ?>"
    data-date-label="<?= e($dateLabel) ?>"
    data-search="<?= e(strtolower($eventTitle . ' ' . $eventVenue . ' ' . $eventCity)) ?>"
    tabindex="0"
    aria-label="<?= e($eventTitle . ', ' . $dateLabel . ', ' . $eventVenue . ', ' . $eventCity) ?>"
    style="--reveal-delay: <?= max(0, $cardIndex) * 60 ?>ms;"
>
    <div class="wf-card-poster">
        <?php if ($eventPoster !== ''): ?>
            <img
                src="<?= e(asset($eventPoster)) ?>"
                alt="NewJeans gallery photo for <?= e($eventTitle) ?>"
                loading="lazy"
                decoding="async">
        <?php endif; ?>
        <time class="wf-card-square" datetime="<?= e($dateIso) ?>" aria-label="<?= e($dateLabel) ?>">
            <span><?= e($monthLabel) ?></span>
            <strong><?= e($dayLabel) ?></strong>
            <small><?= e($yearLabel) ?></small>
        </time>
    </div>
    <div class="wf-card-info">
        <div class="wf-card-head">
            <h3><?= e($eventTitle) ?></h3>
            <p class="wf-time"><?= e($timeLabel) ?></p>
        </div>
        <p class="wf-venue">
            <strong><?= e($eventVenue) ?></strong>
            <span class="wf-venue-city"><?= e($eventCity) ?></span>
        </p>
        <p class="wf-desc"><?= e($eventDescription) ?></p>
    </div>
</article>
