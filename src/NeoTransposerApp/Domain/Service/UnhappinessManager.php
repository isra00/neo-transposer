<?php

namespace NeoTransposerApp\Domain\Service;

use NeoTransposerApp\Domain\Entity\User;
use NeoTransposerApp\Domain\Exception\InvalidStandardRangeException;
use NeoTransposerApp\Domain\Repository\FeedbackRepository;
use NeoTransposerApp\Domain\Repository\UnhappyUserRepository;

class UnhappinessManager
{
    /**
     * The performance below which a user is considered unhappy.
     *
     * @var float
     */
    const UNHAPPY_THRESHOLD_PERF = .5;

    /**
     * The minimum number of feedback reports for considering a user unhappy if
     * their performance < UNHAPPY_THRESHOLD_PERF
     *
     * @var int
     */
    public const UNHAPPY_THRESHOLD_REPORTS = 5;

    protected $unhappyUserRepository;
    protected $neoconfig;
    protected $feedbackRepository;

    public function __construct(
        UnhappyUserRepository $unhappyUserRepository,
        array $neoconfig,
        FeedbackRepository $feedbackRepository
    ) {
        $this->unhappyUserRepository = $unhappyUserRepository;
        $this->neoconfig = $neoconfig;
        $this->feedbackRepository = $feedbackRepository;
    }

    public function setUnhappy(User $user)
    {
        if ($user->performance->score() < self::UNHAPPY_THRESHOLD_PERF && $user->performance->reports(
            ) >= self::UNHAPPY_THRESHOLD_REPORTS) {
            $this->unhappyUserRepository->writeUnhappyUser($user->id_user);
        } elseif ($this->isUnhappyNoAction($user)) {
            //If user was unhappy with no action but their performance is good, delete unhappy.
            $this->unhappyUserRepository->delete($user->id_user);
        }
    }

    public function isUnhappy(User $user): bool
    {
        return $this->unhappyUserRepository->readUserIsUnhappy($user->id_user);
    }

    /**
     * Whether the user is unhappy and has taken no action so far.
     */
    public function isUnhappyNoAction(User $user): bool
    {
        return $this->unhappyUserRepository->readUserIsUnhappyAndNoAction($user->id_user);
    }

    /**
     * Wizard finished debe llamar a este método.
     * @todo Esto es lógica de negocio, debería estar en el domain service que gestiona el wizard
     */
    public function changedVoiceRangeFromWizard(User $user)
    {
        if ($this->isUnhappyNoAction($user)) {
            $this->takeAction($user, 'wizard');
        }
    }

    public function chooseStandard(User $user, string $standard)
    {
        $standardVoices = array_keys($this->neoconfig['voice_wizard']['standard_voices']);

        if (!in_array($standard, $standardVoices)) {
            throw new InvalidStandardRangeException("Invalid standard voice $standard");
        }

        $this->takeAction($user, 'std_' . $standard);
    }

    public function takeAction(User $user, string $action)
    {
        $this->unhappyUserRepository->updateUnhappyUser(
            $action,
            $this->feedbackRepository->readUserPerformance($user->id_user)->score(),
            $user->id_user
        );
    }
}
