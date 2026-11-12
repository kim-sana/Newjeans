<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\GalleryRepository;
use App\Models\NewsRepository;
use App\Models\SongRepository;

final class HomeController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        $featuredSongs = (new SongRepository())->featuredWithAlbum(3);
        $galleryItems = (new GalleryRepository())->publicItems();
        $newsItems = (new NewsRepository())->published(6);

        return $this->renderPage('pages/home', [
            'featuredSongs' => $featuredSongs,
            'galleryItems' => $galleryItems,
            'newsItems' => $newsItems,
        ], [
            'title' => 'NewJeans — Home',
            'description' => 'The NewJeans fan hub — music, discography, schedules, gallery and the latest news from the Bunnies universe.',
        ], 'home', ['carousel.css', 'home.css'], [
            'track-carousel.js',
            'gallery-carousel.js',
            'gallery-controls.js',
        ]);
    }
}
