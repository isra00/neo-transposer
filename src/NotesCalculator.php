<?php

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
}