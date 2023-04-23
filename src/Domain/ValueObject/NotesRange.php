<?php

namespace App\Domain\ValueObject;

use App\Domain\NotesCalculator;

/**
 * @refactor Para que esto sea realmente un value object los atributos deben ser inmutables.
 */
final class NotesRange
{
    public function __construct(
        public ?string $lowest = null,
        public ?string $highest = null
    ) {
    }

    public function isWithinRange(NotesRange $otherRange, NotesCalculator $nc): bool
    {
        return ($nc->distanceWithOctave($this->highest, $otherRange->highest) <= 0)
            && ($nc->distanceWithOctave($otherRange->lowest, $this->lowest) <= 0);
    }

    public function lowest(): ?string
    {
        return $this->lowest;
    }

    public function highest(): ?string
    {
        return $this->highest;
    }
}
