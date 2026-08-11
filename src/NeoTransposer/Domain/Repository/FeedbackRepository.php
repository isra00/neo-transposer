<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Domain\ValueObject\UserPerformance;

interface FeedbackRepository
{
    public function readUserPerformance($idUser): UserPerformance;

    public function createOrUpdateFeedback(
        int $idSong,
        int $idUser,
        bool $worked,
        NotesRange $userRange,
        string $pcStatus,
        float $centeredScoreRate,
        ?int $deviationFromCentered = null,
        ?string $transposition = null
    ): void;

    /**
     * The feedback the user gave about a song, or null if they gave none.
     *
     * @return \stdClass|null With 2 fields: worked (0/1) and transposition, which says
     *                        which one worked (centered1, centered2, notEquivalent or
     *                        peopleCompatible) and is NULL for basic yes/no feedback.
     */
    public function readSongFeedbackForUser(int $idUser, int $idSong): ?\stdClass;
}