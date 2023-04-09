<?php

namespace NeoTransposer\Domain;

class TransposedSongWithFeedback
{
    public function __construct(
        protected TransposedSong $transposedSong,
        protected string $peopleCompatibleStatusMicroMsg,
        protected ?bool $feedbackWorked = null,
        protected ?string $feedbackTranspositionWhichWorked = null)
    {
    }

    public function transposedSong()
    {
        return $this->transposedSong;
    }
}