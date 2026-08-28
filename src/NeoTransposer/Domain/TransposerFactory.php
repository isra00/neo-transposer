<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ValueObject\NotesRange;

class TransposerFactory
{
    private NotesRange $standardPeopleRange;

    public function __construct(
        protected TranspositionFactory $transpositionFactory,
        protected NotesCalculator $notesCalculator
    ) {
        $this->standardPeopleRange = new NotesRange(config('nt.people_range')[0], config('nt.people_range')[1]);
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
            $this->standardPeopleRange, // @todo Leer config aquí y eliminar $this->standardPeopleRange
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }
}
