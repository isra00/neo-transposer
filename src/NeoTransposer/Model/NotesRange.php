<?php

namespace NeoTransposer\Model;

class NotesRange
{
	public $lowest;
	public $highest;

	public function __construct(string $lowest, string $highest)
	{
		$this->lowest  = $lowest;
		$this->highest = $highest;
	}
}