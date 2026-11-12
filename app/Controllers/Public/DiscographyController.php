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

    public function show(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $albums = new AlbumRepository();
        $album = $albums->findWithType($id);

        if ($album === null) {
            return $this->renderPage('pages/404', [
                'message' => "This album isn't available. It may have been removed.",
            ], [
                'title' => 'Album not found — NewJeans',
                'description' => 'The requested NewJeans album could not be found.',
                'status' => 404,
            ], 'discography', ['album-detail.css']);
        }

        $songs = (new SongRepository())->forAlbum($id);
        $releaseDate = (string) ($album['release_date'] ?? '');
        $typeName = (string) ($album['type_name'] ?? '');
        $name = (string) ($album['name'] ?? 'Album');
        $description = $releaseDate !== ''
            ? sprintf('%s — %s released %s. Explore the full tracklist and release details.', $name, $typeName, format_date($releaseDate, 'M j, Y'))
            : sprintf('%s — %s. Explore the full tracklist and release details.', $name, $typeName);

        return $this->renderPage('pages/album-detail', [
            'album' => $album,
            'songs' => $songs,
            'related' => $albums->othersByType((int) ($album['album_type_id'] ?? 0), $id, 3),
        ], [
            'title' => $name . ' — NewJeans',
            'description' => $description,
        ], 'discography', ['album-detail.css'], ['album-detail.js']);
    }
}
