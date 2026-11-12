<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\GalleryRepository;

final class GalleryController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        return $this->renderPage('pages/gallery', [
            'items' => (new GalleryRepository())->publicItems(),
        ], [
            'title' => 'Gallery — NewJeans',
            'description' => 'Explore NewJeans gallery photos in an interactive coverflow carousel.',
        ], 'gallery', ['home.css'], ['gallery-carousel.js', 'gallery-controls.js'], 'gallery-page');
    }
}
