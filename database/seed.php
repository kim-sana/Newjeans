<?php

declare(strict_types=1);

use App\Core\Database;
use Dotenv\Dotenv;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$fresh = in_array('--fresh', $argv, true);
$tables = [
    'users',
    'album_types',
    'albums',
    'songs',
    'news',
    'events',
    'members',
    'histories',
    'gallery_items',
    'order_items',
    'orders',
];

try {
    $pdo = Database::pdo();

    foreach ($tables as $table) {
        $exists = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetchColumn();

        if ($exists === false) {
            fwrite(STDERR, "Table '{$table}' is missing. Import database/schema.sql first.\n");
            exit(1);
        }
    }

    $existingRows = 0;

    foreach ($tables as $table) {
        $existingRows += (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    if ($existingRows > 0 && !$fresh) {
        fwrite(STDERR, "Database already contains seed data. Re-run with --fresh to reset content tables.\n");
        exit(1);
    }

    if ($fresh) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    $pdo->beginTransaction();

    $insert = static function (string $sql, array $params) use ($pdo): int {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return (int) $pdo->lastInsertId();
    };

    $adminPassword = 'admin123';

    $adminId = $insert(
        'INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())',
        ['NewJeans Admin', 'admin@newjeans.local', password_hash($adminPassword, PASSWORD_DEFAULT), 'admin']
    );

    $typeIds = [];

    foreach ([
        ['Mini Album', 'mini-album'],
        ['Single', 'single'],
        ['Collaboration', 'collab'],
    ] as [$name, $slug]) {
        $typeIds[$slug] = $insert(
            'INSERT INTO album_types (name, slug, created_at) VALUES (?, ?, NOW())',
            [$name, $slug]
        );
    }

    $albums = [
        [
            'type' => 'mini-album',
            'name' => 'How Sweet',
            'cover_album' => 'images/albums/howsweet.jpg',
            'release_date' => '2024-05-24 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/0EhZEM4RRz0yioTgucDhJq',
            'youtube_url' => 'https://music.youtube.com/playlist?list=OLAK5uy_lKNaFZbsGnSpGpMuCJ3TGd1HmzZEiWCI',
            'price' => '24.99',
            'stock' => 40,
            'songs' => [
                ['How Sweet', 1, true, false, null],
                ['Bubble Gum', 2, false, false, null],
                ['How Sweet (Instrumental)', 3, false, false, null],
                ['Bubble Gum (Instrumental)', 4, false, false, null],
            ],
        ],
        [
            'type' => 'mini-album',
            'name' => 'Get Up',
            'cover_album' => 'images/albums/get-up.jpg',
            'release_date' => '2023-07-21 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/4N1fROq2oeyLGAlQ1C1j18',
            'youtube_url' => 'https://music.youtube.com/playlist?list=OLAK5uy_nAOTgJLTBDHlMqf0f6HrE0rBRbMn7gmM',
            'price' => '22.99',
            'stock' => 35,
            'songs' => [
                ['New Jeans', 1, false, false, null],
                ['Super Shy', 2, true, true, 'audio/Super-Shy.mp3'],
                ['ETA', 3, false, true, 'audio/ETA.mp3'],
                ['Cool With You', 4, false, false, null],
                ['Get Up', 5, false, false, null],
                ['ASAP', 6, false, false, null],
            ],
        ],
        [
            'type' => 'single',
            'name' => 'OMG',
            'cover_album' => 'images/albums/omg.jpg',
            'release_date' => '2023-01-02 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/45ozep8uHHnj5CCittuyXj',
            'youtube_url' => 'https://music.youtube.com/playlist?list=OLAK5uy_kPiFZWHVoSFi62cOSwlIj9l-gA6FdF3Y',
            'price' => '18.99',
            'stock' => 50,
            'songs' => [
                ['OMG', 1, true, false, null],
                ['Ditto', 2, false, false, null],
            ],
        ],
        [
            'type' => 'mini-album',
            'name' => 'NewJeans (Debut)',
            'cover_album' => 'images/albums/newjeans.jpg',
            'release_date' => '2022-08-01 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/1HMLpmZAnNyl9pxvOnTovV',
            'youtube_url' => 'https://music.youtube.com/playlist?list=OLAK5uy_lk03qnFkqhWCOkLPPGVaGBIgSs8sWEZQ',
            'price' => '19.99',
            'stock' => 45,
            'songs' => [
                ['Attention', 1, true, false, null],
                ['Hype Boy', 2, false, true, 'audio/Hype-Boy.mp3'],
                ['Hurt', 3, false, false, null],
                ['Cookie', 4, false, false, null],
            ],
        ],
        [
            'type' => 'collab',
            'name' => 'Supernatural',
            'cover_album' => 'images/albums/supernatural.jpg',
            'release_date' => '2024-06-21 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/1FVw30SoC91lq1UZ6N9rwN',
            'youtube_url' => 'https://music.youtube.com/playlist?list=OLAK5uy_kUvNJJYJnfGVsq0oMdqf7KOJ8-SKNbD0',
            'price' => '21.99',
            'stock' => 30,
            'songs' => [
                ['Supernatural', 1, true, false, null],
                ['Right Now', 2, false, false, null],
                ['Supernatural (Instrumental)', 3, false, false, null],
                ['Right Now (Instrumental)', 4, false, false, null],
            ],
        ],
        [
            'type' => 'collab',
            'name' => 'Gods',
            'cover_album' => 'images/albums/gods.jpg',
            'release_date' => '2023-10-04 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/4uFXfeGmzFDUrYUyz3Wb1O',
            'youtube_url' => 'https://www.youtube.com/watch?v=G4kbFVfMQPU',
            'price' => '9.99',
            'stock' => 60,
            'songs' => [
                ['Gods', 1, true, false, null],
            ],
        ],
        [
            'type' => 'single',
            'name' => 'Zero',
            'cover_album' => 'images/albums/zero.jpg',
            'release_date' => '2023-04-03 00:00:00',
            'spotify_url' => 'https://open.spotify.com/album/2zYcjcNUzcgMntymoukwZR',
            'youtube_url' => 'https://www.youtube.com/watch?v=bEOFCFxHjl8',
            'price' => '8.99',
            'stock' => 60,
            'songs' => [
                ['Zero', 1, true, false, null],
            ],
        ],
    ];

    foreach ($albums as $album) {
        $albumId = $insert(
            'INSERT INTO albums (album_type_id, name, cover_album, release_date, spotify_url, youtube_url, price, stock, is_for_sale, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
            [
                $typeIds[$album['type']],
                $album['name'],
                $album['cover_album'],
                $album['release_date'],
                $album['spotify_url'],
                $album['youtube_url'],
                $album['price'],
                $album['stock'],
                $album['release_date'],
            ]
        );

        foreach ($album['songs'] as [$title, $trackNumber, $isTitle, $isFeatured, $audioUrl]) {
            $insert(
                'INSERT INTO songs (album_id, title, track_number, is_title, is_featured, audio_url, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $albumId,
                    $title,
                    $trackNumber,
                    (int) $isTitle,
                    (int) $isFeatured,
                    $audioUrl,
                    $album['release_date'],
                ]
            );
        }
    }

    $newsJson = file_get_contents($root . '/database/seed-data/news.json');

    if ($newsJson === false) {
        throw new RuntimeException('Unable to read database/seed-data/news.json');
    }

    $newsItems = json_decode($newsJson, true, 512, JSON_THROW_ON_ERROR);

    foreach ($newsItems as $item) {
        $publishedAt = (new DateTimeImmutable((string) $item['published_at']))->format('Y-m-d H:i:s');

        $insert(
            'INSERT INTO news (created_by, slug, title, thumbnail, content, status, published_at, source_name, source_url, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $adminId,
                $item['slug'],
                $item['title'],
                $item['thumbnail'],
                $item['content'],
                $item['status'] === 'published' ? 'published' : 'draft',
                $publishedAt,
                $item['source_name'],
                $item['source_url'],
                $publishedAt,
            ]
        );
    }

    $events = [
        ['sbs-inkigayo-pre-recording', 'SBS Inkigayo Pre-recording', 'SBS Prism Tower', 'Seoul', '2026-10-28', '03:00', 'images/gallery/gallery1.jpg', 'NewJeans opens the season with an early-morning pre-recording for SBS Inkigayo.'],
        ['supernatural-pop-up-store', "'Supernatural' Pop-Up Store", 'Line Friends Square', 'Shibuya', '2026-10-30', '10:00', 'images/gallery/gallery2.jpg', "A limited pop-up celebrating 'Supernatural' opens in the heart of Shibuya."],
        ['global-fansign-event', 'Global Fansign Event', 'Soundwave Store', 'Seoul', '2026-11-05', '14:00', 'images/gallery/gallery3.jpg', 'Bunnies get closer to the members at an intimate global fansign in Seoul.'],
        ['bunnies-camp-2026-fan-meeting', 'Bunnies Camp 2026: Fan Meeting', 'Tokyo Dome', 'Tokyo', '2026-11-15', '18:00', 'images/gallery/gallery4.jpg', 'The annual Bunnies Camp fan meeting lands at Tokyo Dome for a night of games and performances.'],
        ['m-countdown-comeback-stage', 'M Countdown Comeback Stage', 'CJ ENM Center', 'Seoul', '2026-11-22', '16:00', 'images/gallery/gallery5.jpg', 'NewJeans returns to M Countdown with a special comeback stage.'],
        ['year-end-showcase-concert', 'Year-End Showcase Concert', 'Olympic Gymnastics Arena', 'Seoul', '2026-12-02', '19:00', 'images/gallery/gallery6.jpg', 'A full-scale year-end concert closes the season with fan-favourite hits.'],
        ['bunnies-radio-live-session', 'Bunnies Radio Live Session', 'MBC Radio Studio', 'Seoul', '2026-12-09', '20:00', 'images/gallery/gallery7.jpg', 'The members take over MBC Radio for a live session and Q&A with Bunnies.'],
        ['brand-ambassador-photo-shoot', 'Brand Ambassador Photo Shoot', 'Studio Lumina', 'Seoul', '2026-12-18', '09:00', 'images/gallery/gallery8.jpg', 'An exclusive brand campaign shoot with the full group.'],
        ['asia-tour-bangkok-stop', 'Asia Tour: Bangkok Stop', 'Impact Arena', 'Bangkok', '2026-12-27', '19:30', 'images/gallery/gallery9.jpg', 'The Asia tour continues with a headline show at Impact Arena in Bangkok.'],
        ['music-bank-live-performance', 'Music Bank Live Performance', 'KBS Hall', 'Seoul', '2027-01-03', '17:00', 'images/gallery/gallery10.jpg', 'NewJeans opens the new year with a live performance on Music Bank.'],
        ['asia-tour-jakarta-stop', 'Asia Tour: Jakarta Stop', 'Indonesia Arena', 'Jakarta', '2027-01-12', '19:00', 'images/gallery/gallery11.jpg', 'The Asia tour arrives in Jakarta for a night at Indonesia Arena.'],
        ['bunnies-camp-finale-livestream', 'Bunnies Camp Finale Livestream', 'Online Broadcast', 'Worldwide', '2027-01-20', '21:00', 'images/gallery/gallery12.jpg', 'Bunnies everywhere can join the season finale through a global livestream.'],
    ];

    foreach ($events as [$slug, $title, $venue, $city, $date, $time, $poster, $description]) {
        $eventDate = $date . ' ' . $time . ':00';

        $insert(
            'INSERT INTO events (created_by, slug, title, venue, city, event_date, poster, description, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$adminId, $slug, $title, $venue, $city, $eventDate, $poster, $description, $eventDate]
        );
    }

    $members = [
        ['Minji', 'images/members/minji.jpg', 'Leader · Vocalist', 'Her interests include fashion, music, and mystery novels.'],
        ['Hanni', 'images/members/hanni.jpg', 'Vocalist · Dancer', 'Her interests include art, music, fashion, film, and acting.'],
        ['Haerin', 'images/members/haerin.jpg', 'Vocalist', 'She has spoken about music, fashion, and expressing songs onstage.'],
        ['Hyein', 'images/members/hyein.jpg', 'Maknae · Vocalist', 'She enjoys finding new music and sharing playlists with the members.'],
    ];

    foreach ($members as [$name, $photo, $roles, $description]) {
        $insert(
            'INSERT INTO members (created_by, name, photo, description, member_roles, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$adminId, $name, $photo, $description, $roles]
        );
    }

    $histories = [
        ['images/gallery/gallery1.jpg', 'Mystery Debut', "Debuted with 'Attention' — no teasers, no member reveals. The song exploded globally within hours of release.", '2022-07-22 00:00:00'],
        ['images/gallery/gallery2.jpg', "1st EP 'New Jeans'", "Debut EP featuring 'Attention', 'Hype Boy', 'Cookie', and 'Hurt'. 'Hype Boy' became the longest-running song on Billboard Global 200 by a K-pop female act.", '2022-08-01 00:00:00'],
        ['images/gallery/gallery3.jpg', "'OMG' Single Album", "'Ditto' became the longest-running #1 on Circle Digital Chart and their first Billboard Hot 100 entry.", '2023-01-02 00:00:00'],
        ['images/gallery/gallery4.jpg', "'Get Up' Mini Album", "'Super Shy', 'ETA', 'Cool With You'. Topped Billboard 200. First K-pop girl group to perform at Lollapalooza Chicago.", '2023-07-21 00:00:00'],
    ];

    foreach ($histories as [$image, $title, $description, $eventDate]) {
        $insert(
            'INSERT INTO histories (created_by, image, title, description, event_date, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$adminId, $image, $title, $description, $eventDate, $eventDate]
        );
    }

    for ($index = 1; $index <= 12; $index++) {
        $insert(
            'INSERT INTO gallery_items (created_by, sort_order, image, alt_text, caption, created_at)
             VALUES (?, ?, ?, ?, NULL, NOW())',
            [$adminId, $index, "images/gallery/gallery{$index}.jpg", "NewJeans gallery photo {$index}"]
        );
    }

    $featuredCount = (int) $pdo->query('SELECT COUNT(*) FROM songs WHERE is_featured = 1 AND deleted_at IS NULL')->fetchColumn();

    if ($featuredCount !== 3) {
        throw new RuntimeException("Expected 3 featured songs, found {$featuredCount}.");
    }

    $pdo->commit();

    echo "Seed completed.\n";
    echo "Admin login: admin@newjeans.local / {$adminPassword}\n\n";

    foreach ($tables as $table) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        printf("%-16s %d\n", $table, $count);
    }

    echo "\nAlbum / type / song join check:\n";

    $rows = $pdo->query(
        'SELECT a.name AS album, t.slug AS type_slug, COUNT(s.id) AS song_count
         FROM albums a
         JOIN album_types t ON t.id = a.album_type_id
         LEFT JOIN songs s ON s.album_id = a.id AND s.deleted_at IS NULL
         WHERE a.deleted_at IS NULL
         GROUP BY a.id, a.name, t.slug
         ORDER BY a.release_date DESC'
    )->fetchAll();

    foreach ($rows as $row) {
        printf("%-24s %-12s %d songs\n", $row['album'], $row['type_slug'], $row['song_count']);
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
