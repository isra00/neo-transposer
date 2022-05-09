<?php

namespace NeoTransposerApp\Domain\Repository;

use NeoTransposerApp\Domain\ValueObject\NotesRange;
use NeoTransposerApp\Domain\ValueObject\UserPerformance;

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
