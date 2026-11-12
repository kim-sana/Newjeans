<?php

use App\Core\View;

$events = is_array($events ?? null) ? $events : [];
$nextEvent = is_array($nextEvent ?? null) ? $nextEvent : null;
$countdownLabel = (string) ($countdownLabel ?? '');
$countdownTitle = (string) ($countdownTitle ?? '');
$countdownDate = (string) ($countdownDate ?? '');
$eventCount = count($events);
$hasEvents = $eventCount > 0;
?>
<section class="page-hero dot-blue">
    <div class="container">
        <div class="schedule-hero-chibis">
            <img class="schedule-hero-chibi schedule-hero-chibi--minji" src="<?= e(asset('images/misc/minji-chibi.png')) ?>" alt="Minji chibi">
            <img class="schedule-hero-chibi schedule-hero-chibi--hanni" src="<?= e(asset('images/misc/hanni-chibi.png')) ?>" alt="Hanni chibi">
        </div>
        <h1 class="page-title">Schedule</h1>
        <p class="page-lead">
            Upcoming shows, broadcasts and fan events with date, time, venue
            and city.
        </p>
    </div>
</section>

<section class="section schedule-main dot-paper">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <p class="eyebrow">Save the date</p>
                <h2>Upcoming Schedule</h2>
                <p class="sec-lead">
                    Catch the magical moments of NewJeans performing live on grand
                    international stages.
                </p>
            </div>
            <img class="schedule-bunny" src="<?= e(asset('images/misc/bunny.png')) ?>" alt="NewJeans bunny">
        </div>

        <div class="wf-countdown reveal<?= $hasEvents ? '' : ' is-empty' ?>" id="wfCountdown" aria-live="polite">
            <div class="wf-countdown-left">
                <span class="wf-countdown-label"><?= $hasEvents ? 'Next event' : 'Season status' ?></span>
                <span class="wf-countdown-days" id="wfCountdownDays"><?= e($hasEvents ? $countdownLabel : 'Wrapped') ?></span>
            </div>
            <div class="wf-countdown-right">
                <p class="wf-countdown-title" id="wfCountdownTitle"><?= e($hasEvents ? $countdownTitle : 'All scheduled events have completed') ?></p>
                <p class="wf-countdown-date" id="wfCountdownDate"><?= e($hasEvents ? $countdownDate : 'Stay tuned — the next NewJeans season is loading.') ?></p>
            </div>
        </div>

        <div class="wf-controls reveal">
            <div class="wf-search">
                <svg class="wf-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <label for="scheduleSearch" class="sr-only">Search events</label>
                <input
                    type="text"
                    id="scheduleSearch"
                    placeholder="Search event, venue, or city..."
                    aria-label="Search events by title, venue, or city"
                    autocomplete="off">
            </div>

            <div class="wf-sort">
                <span class="wf-sort-label" id="scheduleSortLabel">Sort by</span>
                <div class="wf-select" id="scheduleSort">
                    <button
                        type="button"
                        class="wf-select-trigger"
                        id="scheduleSortTrigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="scheduleSortMenu"
                        aria-labelledby="scheduleSortLabel scheduleSortValue">
                        <span class="wf-select-value" id="scheduleSortValue">Date (Earliest)</span>
                        <svg class="wf-select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <ul class="wf-select-menu" id="scheduleSortMenu" role="listbox" aria-labelledby="scheduleSortLabel" hidden>
                        <li class="wf-select-option" role="option" tabindex="-1" data-value="date-asc" aria-selected="true">Date (Earliest)</li>
                        <li class="wf-select-option" role="option" tabindex="-1" data-value="date-desc" aria-selected="false">Date (Latest)</li>
                        <li class="wf-select-option" role="option" tabindex="-1" data-value="month-asc" aria-selected="false">Month (A-Z)</li>
                        <li class="wf-select-option" role="option" tabindex="-1" data-value="month-desc" aria-selected="false">Month (Z-A)</li>
                    </ul>
                </div>
            </div>
        </div>

        <p class="wf-result-count" id="wfResultCount" aria-live="polite">
            <?php if ($hasEvents): ?>Showing <?= $eventCount ?> event<?= $eventCount === 1 ? '' : 's' ?><?php endif; ?>
        </p>

        <div class="wf-grid" id="wfGrid" aria-label="Schedule events">
            <?php foreach ($events as $index => $event): ?>
                <?= View::render('partials/event-card', ['event' => $event, 'index' => $index]) ?>
            <?php endforeach; ?>
        </div>

        <div class="wf-empty" id="wfEmpty" style="display: <?= $hasEvents ? 'none' : 'flex' ?>" role="status" aria-live="polite">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5" opacity="0.4"/>
                <path d="M20 20L16.65 16.65" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/>
                <path d="M8 11h6M11 8v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.25"/>
            </svg>
            <p class="wf-empty-title">No upcoming events</p>
            <p class="wf-empty-sub">Try a different keyword or check back soon.</p>
        </div>
    </div>
</section>
