<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Model\NotesCalculator;

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
class CheckSongsRangeConsistency implements AdminTask
{
    protected $songRepository;

    public function __construct(SongRepository $songRepository)
    {
        $this->songRepository = $songRepository;
    }

    public function run(): string
    {
        $songs = $this->songRepository->readAllSongs();

        $nc = new NotesCalculator();

        $output = [];

        foreach ($songs as $song) {
            if ($song['lowest_note'] != $nc->lowestNote(array($song['lowest_note'], $song['highest_note']))) {
                $output[] = $song['id_song'] . ' ' . $song['lowest_note'] . ' is higher than ' . $song['highest_note'] . '!';
            }

            if ($song['lowest_note'] == $song['highest_note']) {
                $output[] = $song['id_song'] . ' highest_note == lowest_note!';
            }

            if (!empty($song['people_lowest_note']) && !empty($song['people_highest_note'])) {
                if ($song['people_lowest_note'] != $nc->lowestNote(
                        array($song['people_lowest_note'], $song['people_highest_note'])
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