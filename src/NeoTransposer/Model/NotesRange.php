<?php

namespace NeoTransposer\Model;

class NotesRange
{
	public $lowest;
	public $highest;

	public function __construct(string $lowest=null, string $highest=null)
	{
		$this->lowest  = $lowest;
		$this->highest = $highest;
	}

	public function isWithinRange(NotesRange $otherRange, NotesCalculator $nc)
	{
		return ($nc->distanceWithOctave($this->highest, $otherRange->highest) <= 0)
			&& ($nc->distanceWithOctave($otherRange->lowest, $this->lowest) <= 0);
	}
}
