<?php

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\FeedbackRepository;
use App\Domain\ValueObject\NotesRange;

final class FeedbackRecorder
{
    public function __construct(
        protected FeedbackRepository $feedbackRepository,
        protected UnhappinessManager $unhappyUser)
    {
    }

    public function recordFeedback(
        User $user,
        int $idSong,
        bool $worked,
        NotesRange $userRange,
        string $pcStatus,
        float $centeredScoreRate,
        ?int $deviationFromCentered,
        ?string $transposition
    ) {

        $this->feedbackRepository->createOrUpdateFeedback(
            $idSong,
            $user->id_user,
            $worked,
            $userRange,
            $pcStatus,
            $centeredScoreRate,
            $deviationFromCentered,
            $transposition
        );

        $user->setPerformance($this->feedbackRepository->readUserPerformance($user->id_user));

		$this->unhappyUser->setUnhappy($user);
    }
}