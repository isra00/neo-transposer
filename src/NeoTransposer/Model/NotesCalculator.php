<?php

namespace NeoTransposer\Model;

/**
 * Calculations on notes and chords.
 */
class NotesCalculator
{
	/**
	 * All the accoustic notes of the scale, including # but not bemol.
	 * 
	 * @var array
	 */
	public $accoustic_scale = array('C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B');

	/**
	 * All the accoustic notes (including # but not bemol) of 4 octaves, like in
	 * a 4-octave numbered_scale. 4 octaves should be enough for voice range.
	 * 
	 * @var array
	 */
	public $numbered_scale = array();

	public function __construct()
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
	 * @param  array $notes Array of numbered notes.
	 * @return string 		The lowest note.
	 */
	public function lowestNote(array $notes)
	{
		$minNumber = count($this->numbered_scale);
		$minNote = null;

		foreach ($notes as $note)
		{
			$number = array_search($note, $this->numbered_scale);

			if ($number === false)
			{
				throw new \InvalidArgumentException("'$note' is not a valid note");
			}

			if ($number < $minNumber)
			{
				$minNumber = $number;
				$minNote = $note;
			}
		}

		return $minNote;
	}

	/**
	 * Reads an element of an array, supporting negative indexes and cyclic index.
	 * 
	 * @param  array 	$array 	Any indexed array.
	 * @param  integer 	$index 	Index to read
	 * @return mixed 			The array element
	 */
	public function arrayIndex($array, $index)
	{
		if (abs($index) > count($array) - 1)
		{
			$index = $index % count($array);
		}

		return ($index < 0)
			? $array[count($array) + $index]
			: $array[$index];
	}

	/**
	 * Transpose a given note with an offset.
	 * 
	 * @param  string 	$note  	The note to transpose
	 * @param  integer 	$offset The offset to transpose.
	 * @return string         	The transposed note.
	 */
	public function transposeNote($note, $offset)
	{
		return $this->arrayIndex($this->numbered_scale, array_search($note, $this->numbered_scale) + $offset);
	}
	
	/**
	 * Calculates the absolute distance (in semitones) between two notes, with octave specified.
	 * 
	 * @param  string|NotesRange 	$note1 	Note, specified as [note name][octave number], e.g. E3. If NotesRange, no need of $note2.
	 * @param  string 				$note2 	Another note, following the same pattern as $note1.
	 * @return integer 			Distance in semitones.
	 */
	public function distanceWithOctave($note1, $note2=null)
	{
		/*if ($note1 instanceof NotesRange)
		{
			$note2 = $note1->highest;
			$note1 = $note1->lowest;
		}*/

		return array_search($note1, $this->numbered_scale) - array_search($note2, $this->numbered_scale);
	}

	public function rangeWideness(NotesRange $range)
	{
		return $this->distanceWithOctave($range->highest, $range->lowest);
	}

	/**
	 * Separates the parts of a chord: fundamental note and attributes.
	 * 
	 * @param  string 	$chordName 	Chord name, in standard notation.
	 * @return array 				Associative array with 'fundamental' and 'attributes' key
	 */
	public function readChord($chordName)
	{
		$regexp = '/^([ABCDEFG]#?b?)([mM45679]*|dim)$/';
		preg_match($regexp, $chordName, $match);

		if (!isset($match[2]))
		{
			throw new \Exception("Chord $chordName not recognized");
		}

		return array('fundamental' => $match[1], 'attributes' => $match[2]);
	}

	/**
	 * Transpose a chord adding or substracting semitones.
	 * 
	 * @param  string 	$chordName 	Chord name, according to the syntax admitted by read_chord().
	 * @param  integer 	$amount 	Number of semitones to add or substract.
	 * @return string 				Final chord.
	 */
	public function transposeChord($chordName, $amount)
	{
		$chord = $this->readChord($chordName);
		$chord['fundamental'];

		$transposedFundamental = $this->arrayIndex(
			$this->accoustic_scale, 
			array_search($chord['fundamental'], $this->accoustic_scale) + $amount
		);

		return $transposedFundamental .  $chord['attributes'];
	}

	/*
	 * Transpose a set of chords adding or substracting semitones.
	 * 
	 * @param  array 	$chordList 	An array of chords.
	 * @param  integer 	$amount 	Number of semitones to add or substract.
	 * @return array 				Final set of chords.
	 */
	public function transposeChords($chordList, $amount)
	{
		$finalList = array();

		foreach ($chordList as $chord)
		{
			$finalList[] = $this->transposeChord($chord, $amount);
		}

		return $finalList;
	}
}
