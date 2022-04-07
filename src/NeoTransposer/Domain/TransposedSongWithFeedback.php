<?php

namespace NeoTransposer\Domain;

class TransposedSongWithFeedback
{
    protected $transposedSong;
    protected $peopleCompatibleStatusMicroMsg;
    protected $feedbackWorked;
    protected $feedbackTranspositionWhichWorked;

    public function __construct(
        TransposedSong $transposedSong,
        string $peopleCompatibleStatusMicroMsg,
        ?bool $feedbackWorked = null,
        ?string $feedbackTranspositionWhichWorked = null
    ) {
        $this->transposedSong = $transposedSong;
        $this->peopleCompatibleStatusMicroMsg = $peopleCompatibleStatusMicroMsg;
        $this->feedbackWorked = $feedbackWorked;
        $this->feedbackTranspositionWhichWorked = $feedbackTranspositionWhichWorked;
    }

    public function transposedSong()
    {
        return $this->transposedSong;
    }
}