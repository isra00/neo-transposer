<?php

namespace NeoTransposerApp\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use NeoTransposerApp\Domain\Repository\SongChordRepository;

/**
 * Check that all songs have chords in correlative order starting by zero.
 *
 * @return array|null Check results (to be displayed).
 */
class CheckChordsOrder implements AdminTask
{
    protected $songChordRepository;

    public function __construct(SongChordRepository $songChordRepository)
    {
        $this->songChordRepository = $songChordRepository;
    }

    /**
     * This method will also be used by chord correction panel
     * @return array
     */
    public function checkChordOrderArray(): array
    {
        $chords = $this->songChordRepository->readAllSongChordsInOrder();

        $incorrect = [];

        $current_song = null;
        $last_position = null;
        foreach ($chords as $chord) {
            if ($current_song != $chord['id_song']) {
                $current_song = $chord['id_song'];

                if ($chord['position'] != 0) {
                    $incorrect[$chord['id_song']] = true;
                }
            }

            if ($chord['position'] != 0 && $chord['position'] != $last_position + 1) {
                $incorrect[$chord['id_song']] = true;
            }

            $last_position = $chord['position'];
        }

        return $incorrect;
    }

    public function run(): string
    {
        $incorrect = $this->checkChordOrderArray();

        return empty($incorrect)
            ? 'NO inconsistencies found :-)'
            : 'Songs with problems: ' . implode(', ', $incorrect);
    }
}
