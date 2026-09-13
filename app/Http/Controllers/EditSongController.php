<?php

namespace App\Http\Controllers;

use App\Support\OfficialWebsiteSongMedia;
use Illuminate\Http\Request;
use NeoTransposer\Domain\Exception\SongNotExistException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;

/**
 * Song-by-song editor for reviewing a whole songbook: all the song and chord data of
 * one song, alongside the songbook page image and the recording linked from the song's url, so
 * the data can be checked against the actual song without leaving the page.
 */
final class EditSongController extends Controller
{
    public function get(
        Request $request,
        SongRepository $songRepository,
        BookRepository $bookRepository,
        ?string $idSong = null
    ) {
        $allBooks = $bookRepository->readAllBooks();
        $song = null;

        // The song picker navigates with JS; the query param is the no-JS fallback.
        $idSong ??= $request->get('id_song') ?: null;

        if ($idSong !== null) {
            try {
                $song = $songRepository->readSongById((int) $idSong);
            } catch (SongNotExistException $e) {
                session()->flash('error', "Song $idSong does not exist");
            }
        }

        $idBook = $song
            ? (int) $song->idBook
            : (int) ($request->get('id_book') ?: array_key_first($allBooks));

        $bookSongs = $songRepository->readBookSongs($idBook)->asArray();

        return response()->view('edit_song', [
            'page_title'   => ($song ? 'Edit: ' . $song->title : 'Edit song') . ' · ' . config('nt.software_name'),
            'page_class'   => 'edit-song-page',
            'all_books'    => $allBooks,
            'id_book'      => $idBook,
            'book_songs'   => $bookSongs,
            'song'         => $song,
            'siblings'     => $song ? $this->siblings($bookSongs, (int) $song->idSong) : ['prev' => null, 'next' => null],
            'media'        => OfficialWebsiteSongMedia::forUrl($song->url ?? null),
        ]);
    }

    public function post(
        Request $request,
        SongRepository $songRepository,
        BookRepository $bookRepository,
        string $idSong
    ) {
        $chords = array_values(array_filter(
            $request->get('chords', []),
            fn ($chord) => (string) $chord !== ''
        ));

        $url = trim((string) $request->get('url')) ?: null;

        $songRepository->updateSong(
            (int) $idSong,
            (int) $request->get('id_book'),
            $request->get('page') !== '' && $request->get('page') !== null ? (int) $request->get('page') : null,
            (string) $request->get('title'),
            strtoupper(trim((string) $request->get('lowest_note'))),
            strtoupper(trim((string) $request->get('highest_note'))),
            strtoupper(trim((string) $request->get('people_lowest_note'))) ?: null,
            strtoupper(trim((string) $request->get('people_highest_note'))) ?: null,
            (bool) $request->get('first_chord_is_key'),
            (string) $request->get('slug'),
            $chords,
            $url,
            $request->get('artistic_adjustment') !== '' && $request->get('artistic_adjustment') !== null
                ? (int) $request->get('artistic_adjustment')
                : null
        );

        // The media embeds come from the url's page, so a new url must be scraped again.
        OfficialWebsiteSongMedia::forget($url);

        session()->flash('success', 'Song saved');

        $goTo = $request->get('go_to_next') ? $this->nextSongId($songRepository, $request, $idSong) : $idSong;

        return redirect()->route('edit_song', ['id_song' => $goTo]);
    }

    private function nextSongId(SongRepository $songRepository, Request $request, string $idSong): string
    {
        $siblings = $this->siblings(
            $songRepository->readBookSongs((int) $request->get('id_book'))->asArray(),
            (int) $idSong
        );

        return (string) ($siblings['next']['id_song'] ?? $idSong);
    }

    /**
     * @param  mixed[]  $bookSongs  Rows of the song's book, in the book's own order.
     * @return array{prev: ?array, next: ?array}
     */
    private function siblings(array $bookSongs, int $idSong): array
    {
        $rows = array_values(array_map(fn ($row) => (array) $row, $bookSongs));
        $positions = array_column($rows, 'id_song');
        $current = array_search($idSong, $positions, false);

        if ($current === false) {
            return ['prev' => null, 'next' => null];
        }

        return [
            'prev' => $rows[$current - 1] ?? null,
            'next' => $rows[$current + 1] ?? null,
        ];
    }
}
