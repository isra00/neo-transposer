<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The songs that are the same song in different books now point to the song in its
 * origin book, which points to itself, instead of sharing a unique_song row.
 */
return new class() extends Migration
{
    public function up(): void
    {
        // Song 947 pointed to a unique_song that does not exist: it is the Swahili
        // "Como lirio entre los cardos" (song 359, unique song 53).
        DB::statement('UPDATE song SET id_unique_song = 53 WHERE id_song = 947');

        DB::statement('ALTER TABLE song ADD COLUMN id_origin_song int unsigned DEFAULT NULL AFTER url, ADD KEY id_origin_song (id_origin_song)');
        DB::statement(
            'UPDATE song s
             JOIN unique_song u ON u.id_unique_song = s.id_unique_song
             JOIN song o ON o.id_unique_song = u.id_unique_song AND o.id_book = u.id_origin_book
             SET s.id_origin_song = o.id_song'
        );
        DB::statement('ALTER TABLE song DROP KEY id_unique_song, DROP COLUMN id_unique_song');
        DB::statement('DROP TABLE unique_song');
    }
};
