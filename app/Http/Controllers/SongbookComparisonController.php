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
    private const STATUS_RANK = ['unlinked' => 5, 'chords' => 4, 'key' => 3, 'range' => 2, 'same' => 1, 'alone' => 0];

    private const FILTERS = [
        'all'        => 'Everything',
        'incomplete' => 'Missing from some book',
        'key'        => 'Key differs',
        'chords'     => 'Chords differ',
        'range'      => 'Voice range differs',
        'exclusive'  => 'In one book only',
        'unlinked'   => 'Not linked to a unique song',
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

        // A song not linked to a unique song yet cannot be compared, but it is listed as a
        // row of its own so the gap shows up and gets fixed.
        $unlinkedSongs = array_map(fn ($song) => [
            'id_unique_song' => null,
            'name'           => $song['title'],
            'id_origin_book' => (int) $song['id_book'],
            'notes'          => null,
            'songs'          => [(int) $song['id_book'] => [$song]],
        ], $uniqueSongRepository->readUnlinkedSongs());

        $rows = [];
        foreach ([...$uniqueSongRepository->readComparison(), ...$unlinkedSongs] as $uniqueSong) {
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
     * @param  array{id_unique_song: ?int, name: string, id_origin_book: ?int, notes: ?string, songs: array<int, mixed[][]>}  $uniqueSong
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
            $uniqueSong['id_unique_song'] === null => 'unlinked',
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
                    $cell['diff']['chords_different'],
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
        $chords = array_values(array_filter(explode(' ', (string) $song['chords'])));
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
            'chord_pitches'     => array_map(fn ($name) => $this->chordPitch(Chord::fromString($name)), $chords),
            'lowest'            => $range->lowest,
            'highest'           => $range->highest,
            'lowest_index'      => $notesCalculator->distanceWithOctave($range->lowest, 'C1'),
            'extension'         => $notesCalculator->rangeWideness($range),
            'people_lowest'     => $peopleRange?->lowest,
            'people_lowest_index' => $peopleRange ? $notesCalculator->distanceWithOctave($peopleRange->lowest, 'C1') : null,
            'people_highest'    => $peopleRange?->highest,
            'people_extension'  => $peopleRange ? $notesCalculator->rangeWideness($peopleRange) : null,
        ];
    }

    /**
     * @return array{int, string} Pitch class of the fundamental, and the attributes.
     */
    private function chordPitch(Chord $chord): array
    {
        return [$this->pitchClass($chord->fundamental), (string) $chord->attributes];
    }

    /**
     * The chords of the song, in order, moved down $shift semitones, as comparable
     * strings ("9m", "5", "7"): two books playing the same harmony in different keys
     * come out identical once the second is shifted by the key difference.
     *
     * @param  array{int, string}[]  $chordPitches
     * @return string[]
     */
    private function shiftedChords(array $chordPitches, int $shift): array
    {
        return array_map(fn ($pitch) => (($pitch[0] - $shift + 24) % 12) . $pitch[1], $chordPitches);
    }

    /**
     * Semitones the cell is above the reference, folded into a tritone either way: a
     * book printing a song 7 semitones up printed it 5 semitones down just as truly.
     *
     * The first chords are compared only when both are the tone. Otherwise (a book
     * opening with an intro chord) the key is unknown, so the shift is inferred as the
     * transposition that makes both chord lists match best, no shift winning ties.
     * A shift that still leaves most chords different is a coincidence, not a key.
     *
     * @param  mixed[]  $cell
     * @param  mixed[]  $referenceCell
     */
    private function keyDelta(array $cell, array $referenceCell): int
    {
        if ($cell['key_pitch_class'] === null || $referenceCell['key_pitch_class'] === null) {
            return 0;
        }

        if ($cell['key_is_tone'] && $referenceCell['key_is_tone']) {
            return (($cell['key_pitch_class'] - $referenceCell['key_pitch_class'] + 18) % 12) - 6;
        }

        $referenceChords = $this->shiftedChords($referenceCell['chord_pitches'], 0);
        $best = 0;
        $bestDifferent = PHP_INT_MAX;
        foreach ([0, 1, -1, 2, -2, 3, -3, 4, -4, 5, -5, -6] as $shift) {
            $different = $this->chordsDifferent($this->shiftedChords($cell['chord_pitches'], $shift), $referenceChords);
            if ($different < $bestDifferent) {
                [$best, $bestDifferent] = [$shift, $different];
            }
        }

        $total = max(count($cell['chord_pitches']), count($referenceCell['chord_pitches']));

        return $bestDifferent * 2 <= $total ? $best : 0;
    }

    /**
     * How many chords of the longer of both songs have no counterpart in the other,
     * through their longest common subsequence: a chord added or dropped in one book
     * counts once, not as a shift that makes every chord after it different.
     *
     * @param  string[]  $a
     * @param  string[]  $b
     */
    private function chordsDifferent(array $a, array $b): int
    {
        $lcs = array_fill(0, count($b) + 1, 0);
        foreach ($a as $chordA) {
            $diagonal = 0;
            foreach ($b as $j => $chordB) {
                $above = $lcs[$j + 1];
                $lcs[$j + 1] = $chordA === $chordB ? $diagonal + 1 : max($lcs[$j + 1], $lcs[$j]);
                $diagonal = $above;
            }
        }

        return max(count($a), count($b)) - $lcs[count($b)];
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
                'chords_different' => 0, 'chords_total' => count($cell['chords']), 'people_compared' => false,
                'status' => $referenceCell === null ? 'same' : 'reference',
            ];
        }

        // A people range that was never recorded (most of the Swahili book) is unknown,
        // not different: colouring it as a difference would drown the real ones.
        $peopleRangeComparable = $cell['people_extension'] !== null && $referenceCell['people_extension'] !== null;

        $keyDelta = $this->keyDelta($cell, $referenceCell);
        $chordsDifferent = $this->chordsDifferent(
            $this->shiftedChords($cell['chord_pitches'], $keyDelta),
            $this->shiftedChords($referenceCell['chord_pitches'], 0)
        );

        $diff = [
            'key'              => $keyDelta !== 0,
            'chords'           => $chordsDifferent > 0,
            'extension'        => !$this->rangeMatches(
                $cell['extension'], $cell['lowest_index'],
                $referenceCell['extension'], $referenceCell['lowest_index'],
                $keyDelta
            ),
            'people_extension' => $peopleRangeComparable && !$this->rangeMatches(
                $cell['people_extension'], $cell['people_lowest_index'],
                $referenceCell['people_extension'], $referenceCell['people_lowest_index'],
                $keyDelta
            ),
        ];

        return $diff + [
            'people_compared'        => $peopleRangeComparable,
            'chords_different'       => $chordsDifferent,
            'chords_total'           => max(count($cell['chords']), count($referenceCell['chords'])),
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
     * Whether a range is the reference's one, moved along with the key: Am E2-A2 matches
     * Em B1-E2. The key delta is folded into a tritone, so the range may have moved by
     * it in either octave direction (Am to Em is -5 or +7).
     */
    private function rangeMatches(int $extension, int $lowestIndex, int $referenceExtension, int $referenceLowestIndex, int $keyDelta): bool
    {
        $shift = $lowestIndex - $referenceLowestIndex;

        return $extension === $referenceExtension
            && abs($shift) < 12
            && ($shift - $keyDelta) % 12 === 0;
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
            'exclusive'  => $row['books_present'] === 1 && $row['status'] !== 'unlinked',
            'unlinked'   => $row['status'] === 'unlinked',
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
            'unlinked'     => 0,
        ];

        foreach ($rows as $row) {
            if ($row['status'] === 'unlinked') {
                $stats['unique_songs']--;
                $stats['unlinked']++;
                $stats['per_book'][array_key_first($row['cells'])]++;

                continue;
            }
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
