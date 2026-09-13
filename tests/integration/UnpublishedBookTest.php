<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * A book whose `published` column is 0 (a songbook still being prepared) must not be
 * reachable from the public interface, neither the book page, nor its songs, nor the
 * sitemap that points search engines at them.
 */
final class UnpublishedBookTest extends TestCase
{
    /**
     * The book URLs are hard-coded per id in routes/web.php, so the fixture has to
     * reuse a real one instead of an out-of-the-way test id.
     */
    private const ID_BOOK = 1;

    private const BOOK_URL = '/nyimbo-njia-neokatekumenato';

    private const SONG_SLUG = 'test-song-unpublished-book';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('book')->insert([
            'id_book'       => self::ID_BOOK,
            'lang_name'     => 'Kiswahili',
            'details'       => 'Tanzania - Kenya 2003',
            'chord_printer' => 'Swahili',
            'locale'        => 'sw',
            'song_count'    => 1,
            'published'     => 0,
        ]);

        DB::table('song')->insert([
            'id_song'             => 900201,
            'id_book'             => self::ID_BOOK,
            'title'               => 'Song of an unpublished book',
            'slug'                => self::SONG_SLUG,
            'lowest_note'         => 'A1',
            'highest_note'        => 'D3',
            'first_chord_is_tone' => 1,
        ]);

        DB::table('song_chord')->insert([
            'id_song' => 900201, 'chord' => 'Am', 'position' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('song_chord')->where('id_song', 900201)->delete();
        DB::table('song')->where('id_song', 900201)->delete();
        DB::table('book')->where('id_book', self::ID_BOOK)->delete();

        parent::tearDown();
    }

    public function test_the_book_page_is_not_found(): void
    {
        $this->get(self::BOOK_URL)->assertStatus(404);
    }

    public function test_its_songs_are_not_found(): void
    {
        $this->get('/transpose/' . self::SONG_SLUG)->assertStatus(404);
    }

    public function test_neither_the_book_nor_its_songs_are_in_the_sitemap(): void
    {
        $sitemap = $this->get('/sitemap.xml');

        $sitemap->assertOk();
        $sitemap->assertDontSee(self::BOOK_URL);
        $sitemap->assertDontSee(self::SONG_SLUG);
    }

    public function test_publishing_the_book_makes_it_public_again(): void
    {
        DB::table('book')->where('id_book', self::ID_BOOK)->update(['published' => 1]);

        $this->get(self::BOOK_URL)->assertOk();
        $this->get('/transpose/' . self::SONG_SLUG)->assertOk();

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertSee(self::BOOK_URL);
        $sitemap->assertSee(self::SONG_SLUG);
    }
}
