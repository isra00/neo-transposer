<?php

namespace NeoTransposer\Model;

/**
 * Represents a transposition of a song, with transported chords, capo, etc.
 */
class PeopleCompatibleTransposition extends Transposition
{
	public $peopleLowestNote;
	public $peopleHighestNote;
}
