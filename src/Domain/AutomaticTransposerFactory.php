<?php

namespace App\Domain;

use App\Domain\ValueObject\NotesRange;

class AutomaticTransposerFactory
{
    public function __construct(
        protected TranspositionFactory $transpositionFactory,
        protected NotesRange $standardPeopleRange,
        protected NotesCalculator $notesCalculator)
    {
    }

    public function createAutomaticTransposer(
        NotesRange $singerRange,
        NotesRange $songRange,
        array $originalChords,
        $firstChordIsKey,
        NotesRange $songPeopleRange = null
    ): AutomaticTransposer {
        return new AutomaticTransposer(
            $this->notesCalculator,
            $this->transpositionFactory,
            $this->standardPeopleRange,
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }
}