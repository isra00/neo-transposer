<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\ValueObject\NotesRange;

final class ReceiveFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    public function test_feedback_without_pc_status_is_recorded_with_null_status(): void
    {
        $idUser = DB::table('user')->insertGetId([
            'email'        => 'receive-feedback-test@example.com',
            'lowest_note'  => 'A1',
            'highest_note' => 'E3',
            'id_book'      => 2,
        ]);
        $idSong = DB::table('song')->value('id_song');

        // The view sends pc_status="" for songs without a status, which Laravel turns into null.
        $this->withSession(['user' => new User('receive-feedback-test@example.com', $idUser, new NotesRange('A1', 'E3'), 2)])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/feedback', ['id_song' => $idSong, 'worked' => 1, 'pc_status' => ''])
            ->assertOk();

        $this->assertDatabaseHas('transposition_feedback', [
            'id_user' => $idUser, 'id_song' => $idSong, 'worked' => 1, 'pc_status' => null,
        ]);
    }
}
