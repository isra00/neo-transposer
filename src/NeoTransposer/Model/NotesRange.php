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
}