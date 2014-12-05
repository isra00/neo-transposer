
/**
 * Transposes an associative array converting keys into values and viceversa.
 * Nested arrays are not supported.
 * 
 * @param  array $array An associative array.
 * @return array Transposed array.
 */
function array_transpose($array)
{
	$output = array();

	foreach ($array as $key=>$value)
	{
		$output[$value] = $key;
	}

	return $output;
}

	/*******************************************************************************
	 * FUNCTIONS AND OTHER STUFF TO MAKE CALCULATIONS ON NOTES
	 ******************************************************************************/

	/**
	 * Equivalence of sharp notes with their bemol enharmonics.
	 * 
	 * @var array
	 */
	protected $enharmonic_to_bemol = array(
		'C#' => 'Db',
		'D#' => 'Eb',
		'F#' => 'Gb',
		'G#' => 'Ab',
		'A#' => 'Bb',
	);
	
	/**
	 * Gets the enharmonic of any note (# to b or viceversa).
	 * For example, given D#, it will output Eb, and viceversa.
	 * 
	 * @param  string $note A note
	 * @return string The enharmonic of the given note.
	 */
	/*function enharmonic($note)
	{
		if (1 == strlen($note))
		{
			return $note;
		}

		if ('#' == $note[1])
		{
			return ($this->enharmonic_to_bemol[$note]) ? $this->enharmonic_to_bemol[$note] : $note;
		}
		else
		{
			$enharmonic_to_sharp = array_transpose($this->enharmonic_to_bemol);
			return isset($enharmonic_to_sharp[$note]) ? $enharmonic_to_sharp[$note] : $note;
		}
	}*/

	/**
	 * Flattens a note, it is, converts a bemol note into its enharmonic (with #).
	 * 
	 * @param  string $note A note.
	 * @return string Flattened note.
	 */
	/*function flatten_note($note)
	{
		return ('b' == substr($note, strlen($note) - 1))
			? enharmonic($note)
			: $note;
	}*/

	/**
	 * Absolute distance (in semitones) between two flat notes.
	 * 
	 * @param  string $note1 A note.
	 * @param  string $note2 Another note.
	 * @return integer Absolute distance.
	 */
	/*function distance($note1, $note2)
	{

		$index1 = array_search($note1, $this->notes_acc);
		$index2 = array_search($note2, $this->notes_acc);

		return max($index1, $index2) - min($index1, $index2);
	}*/

	/**
	 * Substracts 2 flat notes. If $lower is higher than $higher, result will be negative.
	 * 
	 * @param  string $higher Higher note
	 * @param  string $lower Lower note
	 * @return int The distance in semitones
	 */
	/*function distance_abs($higher, $lower)
	{

		$index1 = array_search($higher, $this->notes_acc);
		$index2 = array_search($lower, $this->notes_acc);

		return $index1 - $index2;	
	}*/

	/**
	 * Calculates the absolute distance (in semitones) between two notes, with scale specified.
	 * 
	 * @param  string $note1 Note, specified as [note name][octave number], e.g. E3.
	 * @param  string $note2 Another note, following the same pattern as $note1.
	 * @return integer Distance in semitones.
	 */
	/*function distance_with_octave($note1, $note2)
	{
		//This regexp admits invalid notes like E# of Cb
		$regexp = '/([abcdefg]#?b?)([1-9])/i';

		if (!preg_match($regexp, $note1, $match1) || !preg_match($regexp, $note2, $match2))
		{
			throw new Exception('Invalid note $note1 or $note2');
		}

		$note1_flat = flatten_note($match1[1]);
		$note1_octave = $match1[2];

		$note2_flat = flatten_note($match2[1]);
		$note2_octave = $match2[2];

		return ((max($note1_octave, $note2_octave) - min($note1_octave, $note2_octave)) * 12) - distance_abs($note1_flat, $note2_flat);
	}*/