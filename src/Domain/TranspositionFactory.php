<?php

namespace App\Domain;

use App\Domain\ValueObject\NotesRange;
use Silex\Application;

final class TranspositionFactory
{
    public function __construct(private readonly Application $app)
    {
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