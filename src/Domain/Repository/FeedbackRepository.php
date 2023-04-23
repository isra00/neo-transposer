<?php

namespace App\Domain\Repository;

use App\Domain\ValueObject\NotesRange;
use App\Domain\ValueObject\UserPerformance;

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