<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ValueObject\NotesRange;

class AutomaticTransposerFactory
{
    private NotesRange $standardPeopleRange;

    public function __construct(
        protected TranspositionFactory $transpositionFactory,
        protected NotesCalculator $notesCalculator
    )
    {
        $this->standardPeopleRange = new NotesRange(config('nt.people_range')[0], config('nt.people_range')[1]);
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
            $this->standardPeopleRange, //@todo Leer config aquí y eliminar $this->standardPeopleRange
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }
}
