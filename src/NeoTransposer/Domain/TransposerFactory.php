<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ValueObject\NotesRange;

class TransposerFactory
{
    public function __construct(
        protected TranspositionFactory $transpositionFactory,
        protected NotesCalculator $notesCalculator
    ) {
    }

    public function createTransposer(
        NotesRange $singerRange,
        NotesRange $songRange,
        array $originalChords,
        $firstChordIsKey,
        ?NotesRange $songPeopleRange = null
    ): Transposer {
        return new Transposer(
            $this->notesCalculator,
            $this->transpositionFactory,
            new NotesRange(config('nt.people_range')[0], config('nt.people_range')[1]),
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }
}
