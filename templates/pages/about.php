<?php

$histories = is_array($histories ?? null) ? $histories : [];
$members = is_array($members ?? null) ? $members : [];
$markerClasses = ['tl-marker--pink', 'tl-marker--lime', '', 'tl-marker--blue'];
?>
<section class="page-hero dot-blue about-hero">
    <div class="container">
        <h1 class="page-title">NewJeans</h1>
        <div class="about-hero-grid">
            <p class="page-lead">
                A girl group rewriting the rules of K-pop — debuting with zero
                hype, only music. Four members, one sound: Y2K nostalgia meets the
                future.
            </p>
            <img class="about-hero-avatar about-hero-avatar--minji" src="<?= e(asset('images/misc/minji.png')) ?>" alt="Minji character">
        </div>
        <div class="page-hero-meta">
            <img class="about-hero-avatar about-hero-avatar--haerin" src="<?= e(asset('images/misc/haerin.png')) ?>" alt="Haerin character">
            <img class="about-hero-avatar about-hero-avatar--hyein" src="<?= e(asset('images/misc/hyein.png')) ?>" alt="Hyein character">
        </div>
    </div>
</section>

<section class="section history-section" id="history">
    <div class="container">
        <div class="sec-head sec-head--center reveal">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <p class="eyebrow">How it all started</p>
                <h2>A timeline of moments that shaped NewJeans</h2>
                <p class="sec-lead">
                    From a mystery debut to global stages — every milestone that
                    made them who they are.
                </p>
            </div>
        </div>

        <?php if ($histories === []): ?>
            <p class="sec-lead">Timeline entries will appear here once they are added.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($histories as $index => $history): ?>
                    <?php
                    $image = (string) ($history['image'] ?? '');
                    $eventDate = $history['event_date'] ?? null;
                    $markerClass = $markerClasses[$index % 4] ?? '';
                    ?>
                    <article class="tl-item reveal">
                        <span class="tl-marker <?= e($markerClass) ?>" aria-hidden="true"></span>
                        <div class="tl-card">
                            <?php if ($image !== ''): ?>
                                <img class="tl-image" src="<?= e(asset($image)) ?>" alt="NewJeans group photo" loading="lazy">
                            <?php endif; ?>
                            <div class="tl-content">
                                <time class="tl-year" datetime="<?= e(format_date($eventDate, 'Y-m')) ?>"><?= e(format_date($eventDate, 'F Y')) ?></time>
                                <h3><?= e($history['title'] ?? '') ?></h3>
                                <p><?= e($history['description'] ?? '') ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section members-section dot-paper" id="members">
    <div class="container">
        <div class="sec-head sec-head--center reveal">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <p class="eyebrow">Member profiles</p>
                <h2>Meet the girls behind the magic</h2>
                <p class="sec-lead">
                    Four members, four distinct personalities, one unified sound.
                    Hover a card to say hello.
                </p>
            </div>
        </div>

        <?php if ($members === []): ?>
            <p class="sec-lead">Member profiles will appear here once they are added.</p>
        <?php else: ?>
            <div class="members-grid">
                <?php foreach ($members as $member): ?>
                    <?php
                    $photo = (string) ($member['photo'] ?? '');
                    $memberName = (string) ($member['name'] ?? '');
                    $roles = (string) ($member['member_roles'] ?? '');
                    $description = (string) ($member['description'] ?? '');
                    ?>
                    <article class="member-card reveal">
                        <?php if ($photo !== ''): ?>
                            <img src="<?= e(asset($photo)) ?>" class="member-photo" alt="<?= e($memberName) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="member-wash"></div>
                        <div class="member-footer">
                            <div class="member-info">
                                <h3 class="member-name"><?= e($memberName) ?></h3>
                                <p class="member-job"><?= e($roles) ?></p>
                                <?php if ($description !== ''): ?>
                                    <p class="member-description"><?= e($description) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
