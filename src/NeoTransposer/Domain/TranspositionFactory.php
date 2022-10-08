<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ValueObject\NotesRange;
use Silex\Application;

class TranspositionFactory
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @throws Exception\SongDataException
     */
    public function createTransposition(
        array $chords = [],
        ?int $capo = 0,
        ?bool $asBook = false,
        ?int $offset = 0,
        ?NotesRange $range = null,
        ?int $deviationFromCentered = 0,
        ?NotesRange $peopleRange = null
    ): Transposition {
        return new Transposition(
            $this->app['neoconfig']['chord_scores'],
            $this->app['translator'],
            $chords,
            $capo,
            $asBook,
            $offset,
            $range,
            $deviationFromCentered,
            $peopleRange
        );
    }
}
