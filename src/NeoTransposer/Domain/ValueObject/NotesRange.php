<?php

namespace NeoTransposer\Domain\ValueObject;

use NeoTransposer\Model\NotesCalculator;

/**
 * @refactor Para que esto sea realmente un value object los atributos deben ser inmutables.
 */
class NotesRange
{
	public $lowest;
	public $highest;

	public function __construct(string $lowest=null, string $highest=null)
	{
		$this->lowest  = $lowest;
		$this->highest = $highest;
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
