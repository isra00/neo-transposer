<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Service\SongCreator;

final class InsertSongController extends Controller
{
    public function get(BookRepository $bookRepository, array $extraVars = [])
    {
        return response()->view('insert_song', $extraVars + [
            'page_title' => 'Insert Song · ' . config('nt.software_name'),
            'all_books'  => $bookRepository->readAllBooks(),
            'id_book'    => $extraVars['id_book'] ?? null,
        ]);
    }

    public function post(Request $request, SongCreator $songCreator, BookRepository $bookRepository)
    {
        $songChords = array_values(array_filter(
            $request->get('chords', []),
            fn($chord) => (string) $chord !== ''
        ));

        $songCreator->createSong(
            (int) $request->get('id_book'),
            $request->get('page') ? (int) $request->get('page') : null,
            (string) $request->get('title'),
            strtoupper((string) $request->get('lowest_note')),
            strtoupper((string) $request->get('highest_note')),
            strtoupper((string) $request->get('people_lowest_note')),
            strtoupper((string) $request->get('people_highest_note')),
            (bool) $request->get('first_chord_is_key'),
            $songChords
        );

        session()->flash('success', 'Song inserted');

        return $this->get($bookRepository, ['id_book' => $request->get('id_book')]);
    }
}
