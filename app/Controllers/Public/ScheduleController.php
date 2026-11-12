<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\EventRepository;
use DateTimeImmutable;

final class ScheduleController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        $events = (new EventRepository())->upcoming();
        $nextEvent = $events[0] ?? null;
        $countdownLabel = '';
        $countdownTitle = '';
        $countdownDate = '';

        if (is_array($nextEvent)) {
            $eventDate = (string) ($nextEvent['event_date'] ?? '');
            $timestamp = $eventDate !== '' ? strtotime($eventDate) : false;

            if ($timestamp !== false) {
                $today = new DateTimeImmutable('today');
                $eventDay = new DateTimeImmutable(date('Y-m-d', $timestamp));
                $daysAway = max(0, (int) $today->diff($eventDay)->format('%r%a'));

                $countdownLabel = $daysAway === 0
                    ? 'Today'
                    : ($daysAway === 1 ? 'Tomorrow' : $daysAway . ' days away');
                $countdownTitle = (string) ($nextEvent['title'] ?? '');
                $countdownDate = date('M j, Y', $timestamp)
                    . ' · ' . date('H:i', $timestamp)
                    . ' · ' . (string) ($nextEvent['venue'] ?? '')
                    . ', ' . (string) ($nextEvent['city'] ?? '');
            }
        }

        return $this->renderPage('pages/schedule', [
            'events' => $events,
            'nextEvent' => $nextEvent,
            'countdownLabel' => $countdownLabel,
            'countdownTitle' => $countdownTitle,
            'countdownDate' => $countdownDate,
        ], [
            'title' => 'Schedule — NewJeans',
            'description' => 'NewJeans upcoming schedule — shows, fan meetings, broadcasts and events.',
        ], 'schedule', ['schedule.css'], ['schedule.js']);
    }
}
