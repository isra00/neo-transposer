<?php

namespace App\Domain\AdminTasks;

use App\Domain\NotesCalculator;
use App\Domain\Repository\SongRepository;

/**
 * Check songs that have one of the following conditions:
 * - lowest_note > highest_note
 * - lowest_note == highest_note
 * - people_lowest_note > people_highest_note
 * - people_lowest_note == people_highest_note
 * - people_lowest_note < lowest_note
 * - people_highest_note > highest_note
 *
 * @return string Check results, to be displayed.
 */
final class CheckSongsRangeConsistency implements AdminTask
{
    public function __construct(protected SongRepository $songRepository)
    {
    }

    public function run(): string
    {
        $songs = $this->songRepository->readAllSongs();

        $nc = new NotesCalculator();

        $output = [];

        foreach ($songs as $song) {
            if ($song['lowest_note'] != $nc->lowestNote([$song['lowest_note'], $song['highest_note']])) {
                $output[] = $song['id_song'] . ' ' . $song['lowest_note'] . ' is higher than ' . $song['highest_note'] . '!';
            }

            if ($song['lowest_note'] == $song['highest_note']) {
                $output[] = $song['id_song'] . ' highest_note == lowest_note!';
            }

            if (!empty($song['people_lowest_note']) && !empty($song['people_highest_note'])) {
                if ($song['people_lowest_note'] != $nc->lowestNote(
                        [$song['people_lowest_note'], $song['people_highest_note']]
                    )) {
                    $output[] = $song['id_song'] . ' assembly lowest_note ' . $song['people_lowest_note'] . ' is higher than ' . $song['people_highest_note'] . '!';
                }

                if ($song['people_lowest_note'] == $song['people_highest_note']) {
                    $output[] = $song['id_song'] . ' people_highest_note == people_lowest_note!';
                }

                if (0 > $nc->distanceWithOctave($song['people_lowest_note'], $song['lowest_note'])) {
                    $output[] = $song['id_song'] . ' people_lowest_note < lowest_note!';
                }

                if (0 > $nc->distanceWithOctave($song['highest_note'], $song['people_highest_note'])) {
                    $output[] = $song['id_song'] . ' people_highest_note > highest_note!';
                }
            }
        }

        if (empty($output)) {
            $output[] = 'NO inconsistencies found :-)';
        }

        return implode("\n", $output);
    }
}