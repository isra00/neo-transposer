<?php

namespace NeoTransposerApp\Domain\Service;

use NeoTransposerApp\Domain\Entity\User;
use NeoTransposerApp\Domain\Repository\FeedbackRepository;
use NeoTransposerApp\Domain\ValueObject\NotesRange;

class FeedbackRecorder
{
    protected $feedbackRepository;
    protected $unhappyUser;

    public function __construct(FeedbackRepository $feedbackRepository, UnhappinessManager $unhappyUser)
    {
        $this->feedbackRepository = $feedbackRepository;
        $this->unhappyUser = $unhappyUser;
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
