<?php

namespace NeoTransposer;

/**
 * Calculations on notes.
 */
class NotesCalculator
{
	/**
	 * All the accoustic notes of the scale, including # but not bemol.
	 * 
	 * @var array
	 */
	protected $accoustic_scale = array('C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B');

	/**
	 * All the accoustic notes (including # but not bemol) of 4 octaves, like in a 4-octave numbered_scale.
	 * 4 octaves should be enough for all the singable notes.
	 * 
	 * @var array
	 */
	public $numbered_scale = array();

	function __construct()
	{
		// Fill the numbered_scale.
		for ($i = 1; $i < 5; $i++)
		{
			foreach ($this->accoustic_scale as $note)
			{
				$this->numbered_scale[] = $note . $i;
			}
		}
	}

	/**
	 * Returns the lowest note in the array.
	 * 
	 * @param  array  $notes Array of numbered notes.
	 * @return string The lowest note.
	 */
	public function lowestNote(array $notes)
	{
		$min_number = count($this->numbered_scale);
		$min_note = null;

		foreach ($notes as $note)
		{
			$number = $number = array_search($note, $this->numbered_scale);
			/** Añadir soporte de errores ($number === false) */
			if ($number < $min_number)
			{
				$min_number = $number;
				$min_note = $note;
			}
		}

		return $min_note;
	}

	/**
	 * Reads an element of an array, supporting negative indexes and cyclic index.
	 * 
	 * @param  array $array Any indexed array.
	 * @param  integer $index Index to read
	 * @return mixed The array element
	 */
	function arrayIndex($array, $index)
	{
		if ($index > count($array) - 1)
		{
			$index = $index % count($array);
		}

		return ($index < 0)
			? $array[count($array) + $index]
			: $array[$index];
	}

	function transposeNote($note, $offset)
	{
		return $this->arrayIndex($this->numbered_scale, array_search($note, $this->numbered_scale) + $offset);
	}
	
	/**
	 * Calculates the absolute distance (in semitones) between two notes, with octave specified.
	 * 
	 * @param  string $note1 Note, specified as [note name][octave number], e.g. E3.
	 * @param  string $note2 Another note, following the same pattern as $note1.
	 * @return integer Distance in semitones.
	 *
	 * @todo Pasar a NotesCalculator
	 */
	function distanceWithOctave($note1, $note2)
	{
		return array_search($note1, $this->numbered_scale) - array_search($note2, $this->numbered_scale);
	}


	function getAsOctaveDifference($note)
	{
		preg_match('/([ABCDEFG]#?b?)([0-9])/', $note, $match);
		$note = $match[1];
		$octave = intval($match[2]);
		$octave = $octave - 1;
		return $note . " +$octave";
	}

	function getOnlyNote($note)
	{
		preg_match('/([ABCDEFG]#?b?)([0-9])/', $note, $match);
		return $match[1];
	}

}