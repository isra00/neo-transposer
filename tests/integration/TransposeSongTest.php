<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;

final class TransposeSongTest extends TestCase
{
    public function test_unknown_song_returns404_not_server_error(): void
    {
        $response = $this->get('/transpose/999999');
        $response->assertStatus(404);
    }

    // The integration schema holds no song data, so a valid-song request (the path
    // that used to hit the dead TransposeSongApi branch) can't be exercised here.
    public function test_unknown_song_returns404_for_json_accept_header(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get('/transpose/999999');

        $response->assertStatus(404);
    }
}
