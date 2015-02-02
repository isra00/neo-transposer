<?php

namespace NeoTransposer;

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

	protected static $latinNotes = array(
		'A'		=> 'La',
		'A#'	=> 'Sib',
		'B' 	=> 'Si',
		'C'		=> 'Do',
		'C#'	=> 'Do#',
		'D'		=> 'Re',
		'D#'	=> 'Re#',
		'E'		=> 'Mi',
		'F'		=> 'Fa',
		'F#'	=> 'Fa#',
		'G'		=> 'Sol',
		'G#'	=> 'Sol#'
	);

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
			/** @todo Añadir soporte de errores ($number === false) */
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
	 * @param  string $note   The note to transpose
	 * @param  integer $offset The offset to transpose.
	 * @return string         The transposed note.
	 */
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
	 */
	function distanceWithOctave($note1, $note2)
	{
		return array_search($note1, $this->numbered_scale) - array_search($note2, $this->numbered_scale);
	}

	/**
	 * Separates the parts of a chord: fundamental note and attributes.
	 * 
	 * @param  string $chord_name Chord name, in standard notation.
	 * @return array Associative array with 'fundamental' and 'attributes' key
	 */
	function readChord($chord_name)
	{
		$regexp = '/^([abcdefg]#?b?)([m45679\*]*)$/i';
		preg_match($regexp, $chord_name, $match);

		if (!isset($match[2]))
		{
			throw new \Exception("Chord $chord_name not recognized");
		}

		return array('fundamental' => $match[1], 'attributes' => $match[2]);
	}

	/**
	 * Transports a chord adding or substracting semitones.
	 * 
	 * @param  string $chord_name Chord name, according to the syntax admitted by read_chord().
	 * @param  integer $amount Number of semitones to add or substract.
	 * @return string Final chord.
	 */
	function transportChord($chord_name, $amount)
	{
		$chord = $this->readChord($chord_name);
		$chord['fundamental'];

		$transposedFundamental = $this->arrayIndex(
			$this->accoustic_scale, 
			array_search($chord['fundamental'], $this->accoustic_scale) + $amount
		);

		return $transposedFundamental .  $chord['attributes'];
	}

	/*
	 * Transports a set of chords adding or substracting semitones.
	 * 
	 * @param  array $chord_list An array of chords.
	 * @param  integer $amount Number of semitones to add or substract.
	 * @return array Final set of chords.
	 */
	function transposeChords($chord_list, $amount)
	{
		$final_list = array();

		foreach ($chord_list as $chord)
		{
			$final_list[] = $this->transportChord($chord, $amount);
		}

		return $final_list;
	}

	public static function getNotation($note, $notation)
	{
		return ('latin' == $notation) ? self::$latinNotes[$note] : $note;
	}
}