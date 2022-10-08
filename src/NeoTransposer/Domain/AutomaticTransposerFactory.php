<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ValueObject\NotesRange;

class AutomaticTransposerFactory
{
    protected $transpositionFactory;
    protected $standardPeopleRange;
    protected $notesCalculator;

    public function __construct(
        TranspositionFactory $transpositionFactory,
        NotesRange $standardPeopleRange,
        NotesCalculator $notesCalculator
    ) {
        $this->transpositionFactory = $transpositionFactory;
        $this->standardPeopleRange = $standardPeopleRange;
        $this->notesCalculator = $notesCalculator;
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
