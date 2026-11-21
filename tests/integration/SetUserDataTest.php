<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * /set-user-data writes the logged-in user's book and voice range and answers with a
 * redirect only. The book picker, the voice range form and the unhappy-user standard
 * voice buttons all post to it.
 */
final class SetUserDataTest extends TestCase
{
    use DatabaseTransactions;

    private int $idUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idUser = DB::table('user')->insertGetId([
            'email'        => 'set-user-data-test@example.com',
            'lowest_note'  => 'A1',
            'highest_note' => 'E3',
            'id_book'      => 2,
        ]);

        $this->withSession(['user' => new User(
            'set-user-data-test@example.com',
            $this->idUser,
            new NotesRange('A1', 'E3'),
            2
        )]);
    }

    private function userRow(): array
    {
        return (array) DB::table('user')
            ->where('id_user', $this->idUser)
            ->first(['id_book', 'lowest_note', 'highest_note']);
    }

    public function test_choosing_a_book_saves_it_and_redirects_to_its_page(): void
    {
        $this->post('/set-user-data', ['book' => 3])
            ->assertRedirect(route('book_3'));

        $this->assertSame(3, $this->userRow()['id_book']);
    }

    public function test_setting_the_voice_range_saves_it_and_follows_the_redirect_field(): void
    {
        $this->post('/set-user-data', [
            'lowest_note'  => 'B1',
            'highest_note' => 'D3',
            'redirect'     => '/transpose/some-song',
        ])->assertRedirect('/transpose/some-song');

        $this->assertSame(
            ['id_book' => 2, 'lowest_note' => 'B1', 'highest_note' => 'D3'],
            $this->userRow()
        );
        $this->assertDatabaseHas('log_voice_range', [
            'id_user' => $this->idUser, 'method' => User::METHOD_MANUAL, 'lowest_note' => 'B1', 'highest_note' => 'D3',
        ]);
    }

    public function test_a_highest_note_in_the_first_octave_sends_back_to_the_voice_page_and_saves_nothing(): void
    {
        $this->post('/set-user-data', ['lowest_note' => 'A1', 'highest_note' => 'B1'])
            ->assertRedirectContains('/user/voice?bad_voice_range=1');

        $this->assertSame('E3', $this->userRow()['highest_note']);
    }

    public function test_an_unknown_book_is_a_404_and_saves_nothing(): void
    {
        $this->post('/set-user-data', ['book' => 999999])->assertNotFound();

        $this->assertSame(2, $this->userRow()['id_book']);
    }

    public function test_an_unknown_standard_voice_is_a_400(): void
    {
        $this->post('/set-user-data', ['unhappy_choose_std' => 'robot'])->assertStatus(400);
    }

    public function test_an_anonymous_visitor_is_sent_to_login(): void
    {
        $this->withSession(['user' => new User()])
            ->post('/set-user-data', ['book' => 3])
            ->assertRedirectContains('/login');

        $this->assertSame(2, $this->userRow()['id_book']);
    }

    public function test_get_is_not_allowed_and_saves_nothing(): void
    {
        $this->get('/set-user-data?book=3')->assertStatus(405);

        $this->assertSame(2, $this->userRow()['id_book']);
    }
}
