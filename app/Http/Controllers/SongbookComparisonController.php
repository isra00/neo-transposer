<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UniqueSongRepository;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * Side-by-side comparison of the songbooks: which songs each one has, and how the
 * key, the chords and the voice ranges of the same song differ between them.
 *
 * Songs are compared through the unique_song they belong to. Keys and chords are
 * compared relative to the song's own tone, since a book printing a song in another
 * key is a different fact from a book printing different harmony.
 */
final class SongbookComparisonController extends Controller
{
    /**
     * Rows are shown worst-difference first: what you open the page to look at is the
     * songs the books disagree about, not the hundreds they agree on.
     */
    private const STATUS_RANK = ['chords' => 4, 'key' => 3, 'range' => 2, 'same' => 1, 'alone' => 0];

    private const FILTERS = [
        'all'        => 'Everything',
        'incomplete' => 'Missing from some book',
        'key'        => 'Key differs',
        'chords'     => 'Chords differ',
        'range'      => 'Voice range differs',
        'exclusive'  => 'In one book only',
    ];

    public function get(
        Request $request,
        UniqueSongRepository $uniqueSongRepository,
        BookRepository $bookRepository,
        NotesCalculator $notesCalculator
    ) {
        // A book with no songs yet (a songbook being prepared) has nothing to compare.
        $booksWithSongs = $uniqueSongRepository->readBooksWithSongs();
        $allBooks = array_filter(
            $bookRepository->readAllBooks(),
            fn ($book) => in_array($book->idBook(), $booksWithSongs)
        );

        $selectedBooks = array_values(array_intersect(
            array_map('intval', (array) $request->get('books', array_keys($allBooks))),
            array_keys($allBooks)
        ));
        if ($selectedBooks === []) {
            $selectedBooks = array_keys($allBooks);
        }

        $reference = $request->get('reference', 'origin');
        $filter = array_key_exists($request->get('filter'), self::FILTERS) ? $request->get('filter') : 'all';
        $search = trim((string) $request->get('search'));

        $rows = [];
        foreach ($uniqueSongRepository->readComparison() as $uniqueSong) {
            $row = $this->buildRow($uniqueSong, $selectedBooks, $reference, $notesCalculator);

            // A song none of the selected books has says nothing about them.
            if ($row['books_present'] === 0) {
                continue;
            }

            if ($this->matchesFilter($row, $filter) && $this->matchesSearch($row, $search)) {
                $rows[] = $row;
            }
        }

        usort($rows, fn ($a, $b) => [$b['divergence'], $a['unique_song']['name']]
            <=> [$a['divergence'], $b['unique_song']['name']]);

        return response()->view('songbook_comparison', [
            'page_title'     => 'Songbook comparison · ' . config('nt.software_name'),
            'page_class'     => 'songbook-comparison-page',
            'all_books'      => $allBooks,
            'selected_books' => $selectedBooks,
            'reference'      => $reference,
            'filter'         => $filter,
            'filters'        => self::FILTERS,
            'search'         => $search,
            'rows'           => $rows,
            'stats'          => $this->stats($rows, $selectedBooks),
        ]);
    }

    /**
     * @param  array{id_unique_song: int, name: string, id_origin_book: ?int, notes: ?string, songs: array<int, mixed[][]>}  $uniqueSong
     * @param  int[]  $selectedBooks
     */
    private function buildRow(
        array $uniqueSong,
        array $selectedBooks,
        string $reference,
        NotesCalculator $notesCalculator
    ): array {
        $cells = [];
        foreach ($selectedBooks as $idBook) {
            foreach ($uniqueSong['songs'][$idBook] ?? [] as $song) {
                $cells[$idBook][] = $this->buildCell($song, $notesCalculator);
            }
        }

        $referenceBook = $this->referenceBook($cells, $uniqueSong['id_origin_book'], $reference);
        $referenceCell = $cells[$referenceBook][0] ?? null;

        foreach ($cells as $idBook => $bookCells) {
            foreach ($bookCells as $position => $cell) {
                $cells[$idBook][$position]['diff'] = $this->diff($cell, $referenceCell);
            }
        }

        $differs = $this->rowDiffFlags($cells);

        $status = match (true) {
            // Nothing was compared, so it is neither identical nor different.
            count($cells) === 1 => 'alone',
            $differs['chords'] => 'chords',
            $differs['key'] => 'key',
            $differs['extension'] || $differs['people_extension'] => 'range',
            default => 'same',
        };

        return [
            'unique_song'     => $uniqueSong,
            'cells'           => $cells,
            'reference_book'  => $referenceBook,
            'books_present'   => count($cells),
            'books_missing'   => count($selectedBooks) - count($cells),
            'differs'         => $differs,
            'status'          => $status,
            'divergence'      => [self::STATUS_RANK[$status], $this->widestDifference($cells, $referenceCell)],
        ];
    }

    /**
     * The biggest single gap between the reference and any other book, used to order
     * songs within the same kind of difference: a song printed a tritone away comes
     * before one printed a tone away.
     *
     * @param  array<int, mixed[][]>  $cells
     * @param  ?mixed[]  $referenceCell
     */
    private function widestDifference(array $cells, ?array $referenceCell): int
    {
        $widest = 0;

        foreach ($cells as $bookCells) {
            foreach ($bookCells as $cell) {
                $widest = max(
                    $widest,
                    count(array_diff(
                        array_merge($cell['relative_chords'], $referenceCell['relative_chords'] ?? []),
                        array_intersect($cell['relative_chords'], $referenceCell['relative_chords'] ?? [])
                    )),
                    abs($cell['diff']['key_delta']),
                    abs($cell['diff']['extension_delta']),
                    abs($cell['diff']['people_extension_delta'])
                );
            }
        }

        return $widest;
    }

    /**
     * @param  mixed[]  $song
     */
    private function buildCell(array $song, NotesCalculator $notesCalculator): array
    {
        $chords = array_filter(explode(' ', (string) $song['chords']));
        $key = $chords ? Chord::fromString(reset($chords)) : null;

        $range = new NotesRange($song['lowest_note'], $song['highest_note']);
        $peopleRange = $song['people_lowest_note'] && $song['people_highest_note']
            ? new NotesRange($song['people_lowest_note'], $song['people_highest_note'])
            : null;

        return [
            'id_song'           => (int) $song['id_song'],
            'title'             => $song['title'],
            'key'               => $key ? (string) $key : null,
            'key_is_tone'       => (bool) $song['first_chord_is_tone'],
            'key_pitch_class'   => $key ? $this->pitchClass($key->fundamental) : null,
            'chords'            => $chords,
            'relative_chords'   => $key ? $this->relativeChords($chords, $key) : [],
            'lowest'            => $range->lowest,
            'highest'           => $range->highest,
            'extension'         => $notesCalculator->rangeWideness($range),
            'people_lowest'     => $peopleRange?->lowest,
            'people_highest'    => $peopleRange?->highest,
            'people_extension'  => $peopleRange ? $notesCalculator->rangeWideness($peopleRange) : null,
        ];
    }

    /**
     * The chords of the song as semitones above its own tone, so that two books
     * playing the same harmony in different keys come out identical.
     *
     * @param  string[]  $chords
     * @return int[] Sorted, without duplicates.
     */
    private function relativeChords(array $chords, Chord $key): array
    {
        $degrees = [];
        foreach ($chords as $chord) {
            $degrees[] = (12 + $this->pitchClass(Chord::fromString($chord)->fundamental) - $this->pitchClass($key->fundamental)) % 12;
        }

        $degrees = array_values(array_unique($degrees));
        sort($degrees);

        return $degrees;
    }

    private function pitchClass(string $fundamental): int
    {
        return (int) array_search(
            str_replace(['Db', 'Eb', 'Gb', 'Ab', 'Bb'], ['C#', 'D#', 'F#', 'G#', 'A#'], $fundamental),
            NotesCalculator::ACOUSTIC_SCALE
        );
    }

    /**
     * @param  array<int, mixed[][]>  $cells
     */
    private function referenceBook(array $cells, ?int $idOriginBook, string $reference): ?int
    {
        if ($reference !== 'origin' && isset($cells[(int) $reference])) {
            return (int) $reference;
        }

        if (isset($cells[$idOriginBook])) {
            return $idOriginBook;
        }

        return array_key_first($cells);
    }

    /**
     * How a cell stands against the reference: what differs, by how much, and the
     * single worst kind of difference, which is what the cell is coloured by.
     *
     * @param  mixed[]  $cell
     * @param  ?mixed[]  $referenceCell
     */
    private function diff(array $cell, ?array $referenceCell): array
    {
        if ($referenceCell === null || $cell['id_song'] === $referenceCell['id_song']) {
            return [
                'key' => false, 'chords' => false, 'extension' => false, 'people_extension' => false,
                'key_delta' => 0, 'extension_delta' => 0, 'people_extension_delta' => 0,
                'status' => $referenceCell === null ? 'same' : 'reference',
            ];
        }

        // A people range that was never recorded (most of the Swahili book) is unknown,
        // not different: colouring it as a difference would drown the real ones.
        $peopleRangeComparable = $cell['people_extension'] !== null && $referenceCell['people_extension'] !== null;

        $diff = [
            'key'              => $cell['key_pitch_class'] !== $referenceCell['key_pitch_class'],
            'chords'           => $cell['relative_chords'] !== $referenceCell['relative_chords'],
            'extension'        => $cell['extension'] !== $referenceCell['extension'],
            'people_extension' => $peopleRangeComparable
                && $cell['people_extension'] !== $referenceCell['people_extension'],
        ];

        // Signed, and folded into a tritone either way: a book printing a song 7
        // semitones up printed it 5 semitones down just as truly.
        $keyDelta = $cell['key_pitch_class'] === null || $referenceCell['key_pitch_class'] === null
            ? 0
            : (($cell['key_pitch_class'] - $referenceCell['key_pitch_class'] + 18) % 12) - 6;

        return $diff + [
            'key_delta'              => $keyDelta,
            'extension_delta'        => $cell['extension'] - $referenceCell['extension'],
            'people_extension_delta' => $peopleRangeComparable
                ? $cell['people_extension'] - $referenceCell['people_extension']
                : 0,
            'status' => match (true) {
                $diff['chords'] => 'chords',
                $diff['key'] => 'key',
                $diff['extension'] || $diff['people_extension'] => 'range',
                default => 'same',
            },
        ];
    }

    /**
     * @param  array<int, mixed[][]>  $cells
     */
    private function rowDiffFlags(array $cells): array
    {
        $flags = ['key' => false, 'chords' => false, 'extension' => false, 'people_extension' => false];

        foreach ($cells as $bookCells) {
            foreach ($bookCells as $cell) {
                foreach ($flags as $what => $alreadySet) {
                    $flags[$what] = $alreadySet || $cell['diff'][$what];
                }
            }
        }

        return $flags;
    }

    private function matchesFilter(array $row, string $filter): bool
    {
        return match ($filter) {
            'incomplete' => $row['books_missing'] > 0,
            'key'        => $row['differs']['key'],
            'chords'     => $row['differs']['chords'],
            'range'      => $row['differs']['extension'] || $row['differs']['people_extension'],
            'exclusive'  => $row['books_present'] === 1,
            default      => true,
        };
    }

    private function matchesSearch(array $row, string $search): bool
    {
        if ($search === '') {
            return true;
        }

        $haystack = $row['unique_song']['name'];
        foreach ($row['cells'] as $bookCells) {
            foreach ($bookCells as $cell) {
                $haystack .= ' ' . $cell['title'];
            }
        }

        return str_contains(mb_strtolower($haystack), mb_strtolower($search));
    }

    /**
     * @param  int[]  $selectedBooks
     */
    private function stats(array $rows, array $selectedBooks): array
    {
        $stats = [
            'unique_songs' => count($rows),
            'per_book'     => array_fill_keys($selectedBooks, 0),
            'shared_by_all' => 0,
            'exclusive'    => 0,
        ];

        foreach ($rows as $row) {
            foreach (array_keys($row['cells']) as $idBook) {
                $stats['per_book'][$idBook]++;
            }
            if ($row['books_missing'] === 0) {
                $stats['shared_by_all']++;
            }
            if ($row['books_present'] === 1) {
                $stats['exclusive']++;
            }
        }

        return $stats;
    }
}
