<?php

$eventRows = is_array($events ?? null) ? $events : [];
?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-calendar-event me-1"></i> Events</h3>
        <a href="<?= e(url('/admin/events/create')) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New event
        </a>
    </div>
    <div class="card-body p-0">
        <?php if ($eventRows === []): ?>
            <p class="admin-empty mb-0">No events yet. Add the first schedule entry.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Venue</th>
                            <th>City</th>
                            <th>Event date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventRows as $eventRow): ?>
                            <?php
                            $eventId = (int) ($eventRow['id'] ?? 0);
                            $poster = (string) ($eventRow['poster'] ?? '');
                            $eventDate = $eventRow['event_date'] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($poster !== ''): ?>
                                        <img src="<?= e(asset($poster)) ?>" alt="" class="admin-thumb">
                                    <?php else: ?>
                                        <span class="admin-thumb admin-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($eventRow['title'] ?? '') ?></td>
                                <td><?= e($eventRow['venue'] ?? '') ?></td>
                                <td><?= e($eventRow['city'] ?? '') ?></td>
                                <td>
                                    <?= is_string($eventDate) && $eventDate !== ''
                                        ? e(date('M j, Y H:i', (int) strtotime($eventDate)))
                                        : '<span class="text-secondary">&mdash;</span>' ?>
                                </td>
                                <td class="text-end admin-actions">
                                    <a
                                        href="<?= e(url('/admin/events/' . $eventId . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >Edit</a>
                                    <?= \App\Core\View::render('admin/partials/delete-form', [
                                        'action' => url('/admin/events/' . $eventId . '/delete'),
                                        'confirm' => 'Delete event "' . ($eventRow['title'] ?? '') . '"?',
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
