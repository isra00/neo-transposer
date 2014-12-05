<?php

require_once 'array_utils.php';
require_once 'Transposition.php';

/**
 * Calculate the better transposition for a given song, given the amplitude of the singer's voice.
 */
class AutomaticTransposer
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
	 * @todo ¿Pasar a NotesCalculator?
	 */
	function transposeNote($note, $offset)
	{
		return array_index($this->numbered_scale, array_search($note, $this->numbered_scale) + $offset);
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

	/**
	 * Differentiates the parts of a chord: fundamental note and attributes.
	 * 
	 * @param  string $chord_name Chord name, in standard notation.
	 * @return array Associative array with 'fundamental' and 'attributes' key
	 */
	function readChord($chord_name)
	{
		$regexp = '/([abcdefg]#?b?)([m467\*]*)/i';
		preg_match($regexp, $chord_name, $match);

		if (!isset($match[2]))
		{
			throw new Exception("Chord $chord_name not recognized");
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

		$transported_fundamental = array_index(
			$this->accoustic_scale, 
			array_search($chord['fundamental'], $this->accoustic_scale) + $amount
		);

		return $transported_fundamental .  $chord['attributes'];
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


	/*******************************************************************************
	 *
	 * AUTOMATIC TRANSPOSITION ALGORITHM
	 *
	 * 1) Measure song and singer wideness.
	 * 2) Calculate an offset, which be used to locate the song in the middle of the singer's register, to be more comfortable.
	 * 3) Transport the song's lowest note to the singer's lowest note + offset.
	 * 4) From the transported lowest note, calculate the transported chord.
	 * 5) Calculate capo from the transported chords to avoid unusual chords.
	 *
	 ******************************************************************************/
	function findPerfectTransposition($singer_lowest_note, $singer_highest_note, $song_lowest_note, $song_highest_note, $original_chords)
	{
		/** @todo Usar key o mejor primer acorde? */
		//$song_key = 'E';

		/*
		 * 1) Measure song and singer wideness.
		 */
		$song_wideness = $this->distanceWithOctave($song_highest_note, $song_lowest_note);
		$singer_wideness = $this->distanceWithOctave($singer_highest_note, $singer_lowest_note);

		/*
		 * 2) Calculate the offset
		 * 
		 * If song is wider than singer, we locate it in the bottom, so that when
		 * the song goes high, the singer can sing one octave down. If not (normally),
		 * we locate it in the middle, in order to be more comfortable.
		 *
		 * @todo Checkear si el redondeo del offset es para arriba o para abajo, y decidir...
		 *
		 * @todo ¿Tener en cuenta la diferente amplitud del registro bajo y alto
		 *       para desplazar la transposición perfecta del centro?
		 */
		$offset = ($song_wideness >= $singer_wideness)
			? $offset = 0
			: round(($singer_wideness - $song_wideness) / 2);

		/*
		 * 3) Transpose the song's lowest note to the singer's lowest note + offset.
		 */
		$add_to_transport = ($this->distanceWithOctave($song_lowest_note, $singer_lowest_note) * (-1)) + $offset;

		/*
		 * 4) From the transposed lowest note, calculate the transposed chords.
		 */
		$transported_chords = $this->transposeChords($original_chords, $add_to_transport);

		$perfectTransposition = new Transposition($transported_chords);
		$perfectTransposition->offset = $add_to_transport;

		// If the perfect tone is the same as in the book, return 0.
		if ($add_to_transport == 0)
		{
			$perfectTransposition->setAsBook(true);
		}
		
		return $perfectTransposition;
	}

	/**
	 * Find equivalent transpositions using capo.
	 * 
	 * @param  Transposition $transposition A given transposition without capo.
	 * @param  array $originalChords Original chords of the songs.
	 * @return array Array of <Transposition> with capo from 1 to 5.
	 */
	function findEquivalentsWithCapo(Transposition $transposition, $originalChords)
	{
		/*
		 * 1) Equivalent transpositions using capo. We admit capo 1 to 5, so we
		 * explore transpositions from the given to 5 semitones down.
		 * In this algorithm, we take the first chord as the key, supposing that
		 * all the other chords will be in the key's scale. This is a very pragmatic approach.
		 */
		
		$transpositions_with_capo = array();

		for ($i = 1; $i < 6; $i++)
		{
			/** @todo Añadir detección de asBook */
			$transposedChords = $this->transposeChords($transposition->chords, $i * (-1));

			$transpositions_with_capo[$i] = new Transposition(
				$transposedChords,
				$i,
				($transposedChords == $originalChords),
				$transposition->offset
			);
		}

		return $transpositions_with_capo;
	}

	/**
	 * Find alternative NOT-equivalent, but near (up to 2 semitones up or down) transpositions.
	 * 
	 * @param  array $transposedChords A given set of chords, already fitting singer's voice.
	 * @param  boolean $limitUp Limit the not-equivalent transpositions to not to choose higher transpositions.
	 * @param  boolean $limitDown Limit the not-equivalent transpositions to not to choose lower transpositions.
	 * @return array Array of arrays, each one containing the capo number and chord set.
	 *
	 * @todo  Ojo con los limitDown y limitUp! Ahora mismo el dato que los activa
	 *        (que la amplitud de la canción sea igual o mayor que la voz) está
	 *        solo disponible en el método findPerfectTransposition, o sea, fuera
	 *        de este ámbito. Solución rápida: habilitar una flag como atributo de la clase.
	 *
	 * @todo  Tazama Ilivvyo Vema es ejemplo de que también puede hacer falta una
	 *        alternativa descendente: para (A#1 - G3) la transp. propuesta es
	 *        Dm capo 1 y Bm capo 4. Con alternativa ascendente se propondría
	 *        Em (como en el libro), 1 st por encima de la perfecta; y con una
	 *        alternativa descendente se puede proponer Am capo 5, que son
	 *        acordes fáciles.
	 */
	function findAlternativeNotEquivalent($transposedChords, $limitDown=false, $limitUp=false)
	{

		for ($i = -2; $i < 3; $i++)
		{
			/*$transpositions_with_capo[$i] = new Transposition(
				$this->transposeChords($transposition->chords, $i * (-1)),
				$i,
				false,
				$transposition->offset
			);	*/
		}

	}

	/**
	 * Sorts an array of Transpositions from easiest to hardest.
	 * 
	 * @param  array $transpositions Array of Transpositions, with the score already set.
	 * @return array The sorted array
	 */
	function sortTranspositionsByEase(array $transpositions)
	{
		usort($transpositions, function($a, $b) {
			return ($a->score < $b->score) ? -1 : 1;
		});

		return $transpositions;
	}

	function findTranspositions($singer_lowest_note, $singer_highest_note, $song_lowest_note, $song_highest_note, $originalChords, $limitTranspositions=2)
	{
		$perfectTransposition = $this->findPerfectTransposition(
			$singer_lowest_note,
			$singer_highest_note,
			$song_lowest_note,
			$song_highest_note,
			$originalChords
		);

		$equivalents = $this->findEquivalentsWithCapo($perfectTransposition, $originalChords);
		//$alternativesNotEquivalent = $this->findAlternativeNotEquivalent($perfectTransposition['chords']);

		$perfect_and_equivalent = array_merge(array($perfectTransposition), $equivalents);
		$perfect_and_equivalent = $this->sortTranspositionsByEase($perfect_and_equivalent);

		if ($limitTranspositions)
		{
			$all = $perfect_and_equivalent;
			$perfect_and_equivalent = array();

			for ($i = 0; $i < $limitTranspositions; $i++)
			{
				$perfect_and_equivalent[] = $all[$i];
			}
		}

		return $perfect_and_equivalent;
		//return array($perfect_and_equivalent, $alternativesNotEquivalent);
		//return array($perfectTransposition);
	}

}