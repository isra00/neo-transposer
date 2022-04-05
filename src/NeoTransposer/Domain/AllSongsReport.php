<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\Repository\SongChordRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Model\Song;
use NeoTransposer\Model\TransposedSong;
use NeoTransposer\Model\User;
use NeoTransposer\NeoApp;

class AllSongsReport
{
    protected $songRepository;
    protected $songChordRepository;
    protected $app;

    public const PEOPLE_COMPATIBLE_MICRO_MESSAGES = [
        PeopleCompatibleCalculation::ALREADY_COMPATIBLE   => '',
        PeopleCompatibleCalculation::WIDER_THAN_SINGER    => '',
        PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE   => '',
        PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE  => '',
        PeopleCompatibleCalculation::ADJUSTED_WELL        => ' ★',
        PeopleCompatibleCalculation::ADJUSTED_WIDER       => ' ☆',
        PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER   => '',
        PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA => '',
    ];

    public function __construct(SongRepository $songRepository, SongChordRepository $songChordRepository, NeoApp $app)
    {
        $this->songRepository = $songRepository;
        $this->songChordRepository = $songChordRepository;
        $this->app = $app;
    }

    /**
     * @param int  $idBook
     * @param User $user
     *
     * @return TransposedSongWithFeedback[]
     * @throws \Exception
     */
    public function getAllTranspositions(int $idBook, User $user): array
    {
        $songRows = $this->songRepository->readBookSongsWithUserFeedback($idBook, $user->id_user)->asArray();

        $songs = [];

        foreach ($songRows as $songRow) {

            /** @refactor Performance: make a single query for all chords of all songs of the given book */
            $transposedSong = new TransposedSong(
                new Song($songRow, $this->songChordRepository->readSongChords($songRow['id_song'])),
                $this->app
            );

            $transposedSong->transpose($user->range);

            $feedbackWorked = $songRow['worked'];
            $feedbackTranspositionWhichWorked = $songRow['transposition_which_worked'];

            /** @see https://github.com/isra00/neo-transposer/issues/129#issuecomment-1086611165 */
            if (
                ("peopleCompatible" == $feedbackTranspositionWhichWorked && empty(
                    $transposedSong->getPeopleCompatible()
                    ))
                || ("notEquivalent" == $feedbackTranspositionWhichWorked && empty($transposedSong->not_equivalent))
            ) {
                $feedbackWorked = false;
                $feedbackTranspositionWhichWorked = null;
            }

            //Remove bracketed text from song title (used for clarifications)
            /** @todo Remove this: bracketed text differentiates variants! */
            $transposedSong->song->title = preg_replace('/(.)\[.*\]/', '$1', $transposedSong->song->title);

            $transposedSongWithFeedback = new TransposedSongWithFeedback(
                $transposedSong,
                self::PEOPLE_COMPATIBLE_MICRO_MESSAGES[$transposedSong->getPeopleCompatibleStatus()],
                $feedbackWorked,
                $feedbackTranspositionWhichWorked
            );

            $songs[] = $transposedSongWithFeedback;
        }

        return $songs;
    }
}