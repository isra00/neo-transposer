<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/**
 * The comparison page's whole point is telling apart "the same song in another key"
 * from "the same song with different harmony", so the fixture below is one unique
 * song printed in two books: same chord progression, different key, different range.
 */
final class SongbookComparisonTest extends TestCase
{
    private const PASSWORD = 'correct-horse-battery-staple';

    private const ID_UNIQUE_SONG = 900001;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nt.admins' => ['tester' => ['ROLE_ADMIN', password_hash(self::PASSWORD, PASSWORD_DEFAULT)]]]);

        DB::table('book')->insert([
            ['id_book' => 901, 'lang_name' => 'Testish', 'details' => 'Testville 2020', 'chord_printer' => 'English', 'locale' => 'ts', 'song_count' => 1],
            ['id_book' => 902, 'lang_name' => 'Mockish', 'details' => 'Mocktown 2021', 'chord_printer' => 'English', 'locale' => 'mk', 'song_count' => 1],
        ]);

        DB::table('unique_song')->insert([
            'id_unique_song' => self::ID_UNIQUE_SONG,
            'name'           => 'Test unique song',
            'id_origin_book' => 901,
        ]);

        // Am F G in one book, Dm A# C in the other: same degrees (i, bVI, bVII) over the tone.
        $this->insertSong(900101, 901, 'Song in the origin book', 'A1', 'D3', ['Am', 'F', 'G']);
        $this->insertSong(900102, 902, 'Song in the other book', 'A1', 'C3', ['Dm', 'A#', 'C']);
    }

    protected function tearDown(): void
    {
        DB::table('song_chord')->whereIn('id_song', [900101, 900102])->delete();
        DB::table('song')->whereIn('id_song', [900101, 900102])->delete();
        DB::table('unique_song')->where('id_unique_song', self::ID_UNIQUE_SONG)->delete();
        DB::table('book')->whereIn('id_book', [901, 902])->delete();
        parent::tearDown();
    }

    public function test_it_shows_the_song_of_every_book_that_has_it(): void
    {
        $response = $this->getComparison();

        $response->assertOk();
        $response->assertSee('Test unique song');
        $response->assertSee('Song in the origin book');
        $response->assertSee('Song in the other book');
    }

    public function test_a_different_key_is_flagged_but_the_same_progression_is_not(): void
    {
        $html = $this->getComparison()->getContent();
        $row = $this->rowOf($html);

        $this->assertStringContainsString('sc-flag sc-is-key', $row, 'Am against Dm is a key difference');
        $this->assertStringNotContainsString('sc-flag sc-is-chords', $row, 'both books play i-bVI-bVII, so the harmony is the same');
    }

    public function test_the_card_says_same_chords_in_a_different_key(): void
    {
        $row = $this->rowOf($this->getComparison()->getContent());

        $this->assertStringContainsString('same ✔︎', $row);
        $this->assertStringContainsString('different key', $row);
        $this->assertStringNotContainsString('/3 different', $row);
    }

    public function test_the_card_counts_the_chords_that_differ(): void
    {
        // Dm A# C against Dm A# C7: one chord of three no longer matches.
        DB::table('song_chord')->where(['id_song' => 900102, 'position' => 3])->update(['chord' => 'C7']);

        $this->assertStringContainsString('1/3 different', $this->rowOf($this->getComparison()->getContent()));
    }

    public function test_a_different_voice_range_is_flagged(): void
    {
        $this->assertStringContainsString(
            'sc-flag sc-is-range',
            $this->rowOf($this->getComparison()->getContent()),
            'A1-D3 against A1-C3 is a range difference'
        );
    }

    public function test_a_range_moved_along_with_the_key_matches(): void
    {
        // Am E2-A2 against Em B1-E2: the same range, a fourth lower like the key.
        DB::table('song')->where('id_song', 900101)->update(['lowest_note' => 'B1', 'highest_note' => 'E2']);
        DB::table('song_chord')->where('id_song', 900101)->delete();
        DB::table('song_chord')->where('id_song', 900102)->delete();
        DB::table('song')->where('id_song', 900102)->update(['lowest_note' => 'E2', 'highest_note' => 'A2']);
        foreach (['Em', 'Am'] as $position => $chord) {
            DB::table('song_chord')->insert(['id_song' => 900101, 'chord' => $chord, 'position' => $position + 1]);
        }
        foreach (['Am', 'Dm'] as $position => $chord) {
            DB::table('song_chord')->insert(['id_song' => 900102, 'chord' => $chord, 'position' => $position + 1]);
        }

        $row = $this->rowOf($this->getComparison()->getContent());

        $this->assertStringContainsString('sc-flag sc-is-key', $row);
        $this->assertStringNotContainsString('sc-flag sc-is-range', $row);
    }

    public function test_a_range_not_moved_along_with_the_key_differs(): void
    {
        // Same width as the reference, but it stayed put while the key moved.
        DB::table('song')->where('id_song', 900101)->update(['lowest_note' => 'A1', 'highest_note' => 'C3']);

        $this->assertStringContainsString('sc-flag sc-is-range', $this->rowOf($this->getComparison()->getContent()));
    }

    public function test_a_leading_chord_that_is_not_the_tone_is_not_a_key_difference(): void
    {
        // The reference opens with an intro E that is not the tone; both are in A.
        DB::table('song')->where('id_song', 900101)->update(['first_chord_is_tone' => 0]);
        DB::table('song_chord')->whereIn('id_song', [900101, 900102])->delete();
        foreach (['E', 'A', 'B', 'E7', 'C#', 'F#m'] as $position => $chord) {
            DB::table('song_chord')->insert(['id_song' => 900101, 'chord' => $chord, 'position' => $position + 1]);
        }
        foreach (['A', 'E', 'B7', 'E7', 'C#', 'F#m'] as $position => $chord) {
            DB::table('song_chord')->insert(['id_song' => 900102, 'chord' => $chord, 'position' => $position + 1]);
        }

        $row = $this->rowOf($this->getComparison()->getContent());

        $this->assertStringNotContainsString('sc-flag sc-is-key', $row);
        $this->assertStringContainsString('2/6 different', $row);
    }

    public function test_a_book_without_the_song_is_shown_as_missing(): void
    {
        // Only the origin book selected: the song is 1 of 1, so nothing is missing.
        $this->assertStringContainsString('1/1', $this->rowOf($this->getComparison(['books' => [901]])->getContent()));
    }

    /**
     * @param  mixed[]  $query
     */
    private function getComparison(array $query = []): TestResponse
    {
        $query += ['books' => [901, 902]];

        return $this->withServerVariables([
            'PHP_AUTH_USER' => 'tester',
            'PHP_AUTH_PW'   => self::PASSWORD,
        ])->get('/admin/songbook-comparison?' . http_build_query($query));
    }

    /**
     * The page holds every unique song, so the assertions must look at this one's row.
     */
    private function rowOf(string $html): string
    {
        $start = strpos($html, 'Test unique song');
        $this->assertNotFalse($start, 'the test unique song is not on the page');

        return substr($html, $start, (int) (strpos($html, '</tr>', $start) - $start));
    }

    /**
     * @param  string[]  $chords
     */
    private function insertSong(int $idSong, int $idBook, string $title, string $lowest, string $highest, array $chords): void
    {
        DB::table('song')->insert([
            'id_song'             => $idSong,
            'id_book'             => $idBook,
            'title'               => $title,
            'slug'                => 'test-song-' . $idSong,
            'lowest_note'         => $lowest,
            'highest_note'        => $highest,
            'first_chord_is_tone' => 1,
            'id_unique_song'      => self::ID_UNIQUE_SONG,
        ]);

        foreach ($chords as $position => $chord) {
            DB::table('song_chord')->insert([
                'id_song'  => $idSong,
                'chord'    => $chord,
                'position' => $position + 1,
            ]);
        }
    }
}
