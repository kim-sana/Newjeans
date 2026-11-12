<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Public\Concerns\PublicViewTrait;
use App\Core\Request;
use App\Core\Response;
use App\Models\AlbumRepository;
use App\Models\AlbumTypeRepository;
use App\Models\SongRepository;

final class DiscographyController
{
    use PublicViewTrait;

    public function index(Request $request, array $params = []): Response
    {
        $albums = (new AlbumRepository())->allWithTypeAndSongCount();
        $songs = new SongRepository();
        $releases = [];

        foreach ($albums as $album) {
            $album['songs'] = $songs->forAlbum((int) ($album['id'] ?? 0));
            $releases[] = $album;
        }

        $albumTypes = (new AlbumTypeRepository())->allWithAlbumCounts();

        return $this->renderPage('pages/discography', [
            'releases' => $releases,
            'albumTypes' => $albumTypes,
        ], [
            'title' => 'Discography — NewJeans',
            'description' => 'NewJeans full discography — albums, singles and collaborations.',
        ], 'discography', ['discography.css'], ['discography.js']);
    }
}
