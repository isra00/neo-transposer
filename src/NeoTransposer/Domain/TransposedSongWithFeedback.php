<?php

namespace NeoTransposer\Domain;

final class TransposedSongWithFeedback
{
    public function __construct(
        public TransposedSong $transposedSong,
        public string $peopleCompatibleStatusMicroMsg,
        public ?bool $feedbackWorked = null,
        public ?string $feedbackTranspositionWhichWorked = null)
    {
    }
}
