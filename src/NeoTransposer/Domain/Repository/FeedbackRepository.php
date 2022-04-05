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

    public function readSongFeedbackForUser(int $idUser, int $idSong): ?bool;
}