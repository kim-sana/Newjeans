<?php

$errors = form_errors();
$eventModel = is_array($model ?? null) ? $model : [];
$eventId = isset($eventModel['id']) ? (int) $eventModel['id'] : 0;
$isEdit = $eventId > 0;

$slugValue = (string) old('slug', $eventModel['slug'] ?? '');
$titleValue = (string) old('title', $eventModel['title'] ?? '');
$venueValue = (string) old('venue', $eventModel['venue'] ?? '');
$cityValue = (string) old('city', $eventModel['city'] ?? '');
$posterValue = (string) old('poster', $eventModel['poster'] ?? '');
$descriptionValue = (string) old('description', $eventModel['description'] ?? '');

$eventDateRaw = (string) old('event_date', $eventModel['event_date'] ?? '');
$eventDateValue = '';
if ($eventDateRaw !== '') {
    $eventTimestamp = strtotime($eventDateRaw);
    $eventDateValue = $eventTimestamp === false ? $eventDateRaw : date('Y-m-d', $eventTimestamp);
}

$action = $isEdit ? url('/admin/events/' . $eventId) : url('/admin/events');
?>
<div class="card admin-form-card">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= $isEdit ? 'Edit event' : 'New event' ?></h3>
    </div>
    <div class="card-body">
        <?= \App\Core\View::render('admin/partials/form-errors', ['errors' => $errors]) ?>

        <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="eventTitle" class="form-label">Title</label>
                    <input
                        type="text"
                        id="eventTitle"
                        name="title"
                        value="<?= e($titleValue) ?>"
                        class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>"
                        maxlength="200"
                        required
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label for="eventDate" class="form-label">Event date</label>
                    <input
                        type="date"
                        id="eventDate"
                        name="event_date"
                        value="<?= e($eventDateValue) ?>"
                        class="form-control<?= isset($errors['event_date']) ? ' is-invalid' : '' ?>"
                    >
                    <?php if (isset($errors['event_date'])): ?>
                        <div class="invalid-feedback"><?= e($errors['event_date']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label for="eventSlug" class="form-label">Slug</label>
                    <input
                        type="text"
                        id="eventSlug"
                        name="slug"
                        value="<?= e($slugValue) ?>"
                        class="form-control<?= isset($errors['slug']) ? ' is-invalid' : '' ?>"
                        maxlength="190"
                        placeholder="bunnies-camp-2026"
                    >
                    <?php if (isset($errors['slug'])): ?>
                        <div class="invalid-feedback"><?= e($errors['slug']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Leave blank to generate from the title. Slugs are kept unique automatically.</div>
                </div>

                <div class="col-md-4">
                    <label for="eventCity" class="form-label">City</label>
                    <input
                        type="text"
                        id="eventCity"
                        name="city"
                        value="<?= e($cityValue) ?>"
                        class="form-control<?= isset($errors['city']) ? ' is-invalid' : '' ?>"
                        maxlength="120"
                        required
                    >
                    <?php if (isset($errors['city'])): ?>
                        <div class="invalid-feedback"><?= e($errors['city']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="eventVenue" class="form-label">Venue</label>
                    <input
                        type="text"
                        id="eventVenue"
                        name="venue"
                        value="<?= e($venueValue) ?>"
                        class="form-control<?= isset($errors['venue']) ? ' is-invalid' : '' ?>"
                        maxlength="160"
                        required
                    >
                    <?php if (isset($errors['venue'])): ?>
                        <div class="invalid-feedback"><?= e($errors['venue']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <?= \App\Core\View::render('admin/partials/image-field', [
                        'name' => 'poster',
                        'label' => 'Poster',
                        'current' => $posterValue,
                        'required' => false,
                        'error' => $errors['poster'] ?? null,
                        'help' => 'Optional. JPEG, PNG, WebP, or GIF up to 5 MB.' . ($isEdit ? ' Leave empty to keep the current poster.' : ''),
                    ]) ?>
                </div>

                <div class="col-12">
                    <label for="eventDescription" class="form-label">Description</label>
                    <textarea
                        id="eventDescription"
                        name="description"
                        rows="6"
                        class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>"
                        required
                    ><?= e($descriptionValue) ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create event' ?></button>
                <a href="<?= e(url('/admin/events')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php clear_form_state(); ?>
