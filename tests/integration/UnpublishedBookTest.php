<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * A book whose language is flagged `unpublished` in config nt.languages (a songbook still being prepared) must not be
 * listed anywhere in the public interface: not in the sitemap, the language switch, the
 * book lists or the language autodetection. Its book page, songs and language pages are
 * still reachable by direct link.
 */
final class UnpublishedBookTest extends TestCase
{
    /**
     * The book URLs are hard-coded per id in routes/web.php, so the fixture has to
     * reuse a real one instead of an out-of-the-way test id. The book itself comes
     * from song_data.sql; the test only unpublishes its language.
     */
    private const ID_BOOK = 1;

    private const BOOK_URL = '/nyimbo-njia-neokatekumenato';

    private const SONG_SLUG = 'test-song-unpublished-book';

    protected function setUp(): void
    {
        parent::setUp();

        config(['nt.languages.sw.unpublished' => true]);

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
        parent::tearDown();
    }

    public function test_the_book_page_is_reachable_by_direct_link(): void
    {
        $this->get(self::BOOK_URL)->assertOk();
    }

    public function test_its_songs_are_reachable_by_direct_link(): void
    {
        $this->get('/transpose/' . self::SONG_SLUG)->assertOk();
    }

    public function test_its_language_pages_are_reachable_by_direct_link(): void
    {
        $this->get('/sw/login')->assertOk();
        $this->get('/sw/people-compatible-transpositions')->assertOk();
    }

    public function test_neither_the_book_nor_its_songs_nor_its_language_are_in_the_sitemap(): void
    {
        $sitemap = $this->get('/sitemap.xml');

        $sitemap->assertOk();
        $sitemap->assertDontSee(self::BOOK_URL);
        $sitemap->assertDontSee(self::SONG_SLUG);
        $sitemap->assertDontSee('/sw/login');
    }

    public function test_its_language_is_not_in_the_language_switch(): void
    {
        $this->get('/en/login')->assertOk()->assertDontSee('/sw/login');
    }

    public function test_it_is_not_in_the_book_chooser_of_other_books(): void
    {
        $this->get('/songs-neocatechumenal-way')->assertOk()->assertDontSee(self::BOOK_URL);
    }

    public function test_visitors_from_its_country_are_not_sent_to_its_language(): void
    {
        $this->withHeaders(['Accept-Language' => 'sw-TZ,sw;q=0.9'])
            ->get('/')
            ->assertRedirect('/en/login');
    }

    public function test_publishing_the_book_makes_it_public_again(): void
    {
        config(['nt.languages.sw.unpublished' => false]);

        $this->get(self::BOOK_URL)->assertOk();
        $this->get('/transpose/' . self::SONG_SLUG)->assertOk();

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertSee(self::BOOK_URL);
        $sitemap->assertSee(self::SONG_SLUG);
    }
}
