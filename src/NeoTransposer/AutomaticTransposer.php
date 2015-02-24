<?php

namespace NeoTransposer;

/**
 * Calculate the better transposition for a given song, given the amplitude of the singer's voice.
 */
class AutomaticTransposer
{
	/**
	 * Instance of NotesCalculator used for calculating transpositions.
	 * @var NotesCalculator
	 */
	protected $nc;

	protected $singer_lowest_note;
	protected $singer_highest_note;
	protected $song_lowest_note;
	protected $song_highest_note;
	protected $original_chords;
	protected $first_chord_is_tone;

	/**
	 * The offset applied for the perfect transposition.
	 * @var integer
	 */
	protected $perfectOffset;

	/**
	 * The calculated perfect transposition.
	 * @var Transposition
	 */
	protected $perfectTransposition;

	/**
	 * The calculated perfect and equivalent transpositions, sorted by ease.
	 * @var array
	 */
	protected $perfectAndEquivalent;

	/**
	 * Offsets used for not equivalent transpositions.
	 * Right now, it's only 1 semitone down and 1 semitone up.
	 * 
	 * @var array
	 */
	protected $offsetsNotEquivalent = array(-1, 1);

	/**
	 * Constructor needs all the data to calculate the transpositions.
	 * 
	 * @param  string $singer_lowest_note  Singer's lowest note
	 * @param  string $singer_highest_note Singer's highest note
	 * @param  string $song_lowest_note    Song's lowest note
	 * @param  string $song_highest_note   Song's highest note
	 * @param  array $original_chords      Song original chords
	 */
	function __construct($singer_lowest_note, $singer_highest_note, $song_lowest_note, $song_highest_note, $original_chords, $first_chord_is_tone)
	{
		$this->singer_lowest_note = $singer_lowest_note;
		$this->singer_highest_note = $singer_highest_note;
		$this->song_lowest_note = $song_lowest_note;
		$this->song_highest_note = $song_highest_note;
		$this->original_chords = $original_chords;
		$this->first_chord_is_tone = $first_chord_is_tone;

		$this->nc = new NotesCalculator;
	}

	/**
	 * This is the core algorithm for Automatic transposition.
	 *
	 * Given the the lowest and highest note of the singer and of the song,
	 * the algorithm tries to locate the song in the middle of the singer's
	 * voice range through simple arithmetics: once calculated the offset
	 * between the original song lowest note and the ideal position, we
	 * transpose each chord using that offset.
	 * 
	 * @param boolean $forceHighestNote Used only in wizard to find highest note.
	 * @param boolean $forceLowestNote Used only in wizard to find highest note.
	 * @return Transposition The transposition matching that voice.
	 */
	function getPerfectTransposition($forceHighestNote=false, $forceLowestNote=false)
	{
		if (!empty($this->perfectTransposition))
		{
			return $this->perfectTransposition;
		}

		/*
		 * 1) Measure song and singer wideness.
		 */
		$song_wideness = $this->nc->distanceWithOctave($this->song_highest_note, $this->song_lowest_note);
		$singer_wideness = $this->nc->distanceWithOctave($this->singer_highest_note, $this->singer_lowest_note);

		/*
		 * 2) Calculate the offset
		 * 
		 * If song is wider than singer, we locate it in the bottom, so that when
		 * the song goes high, the singer can sing one octave down. If not (normally),
		 * we locate it in the middle, in order to be more comfortable. Note that
		 * when the middle ((singer_wideness - song_wideness) / 2) is not an
		 * integer, it will be rounded up. These behavior could be changed taking
		 * into account the preferences of the user.
		 */
		$offset_from_singer_lowest = ($song_wideness >= $singer_wideness)
			? 0
			: round(($singer_wideness - $song_wideness) / 2);

		if ($forceHighestNote)
		{
			$offset_from_singer_lowest = $singer_wideness - $song_wideness;
		}
		
		if ($forceLowestNote)
		{
			$offset_from_singer_lowest = 0;
		}

		/*
		 * 3) Calculate the offset for transposition given the singer's lowest
		 * note and the song and singer wideness.
		 */
		$perfect_offset = intval(
			(-1) * $this->nc->distanceWithOctave($this->song_lowest_note, $this->singer_lowest_note)
			+ $offset_from_singer_lowest
		);

		/*
		 * 4) Transpose the chords with the calculated offset.
		 */
		$transported_chords = $this->nc->transposeChords($this->original_chords, $perfect_offset);

		$perfectTransposition = new Transposition(
			$transported_chords,
			0,
			false,
			$perfect_offset,
			$this->nc->transposeNote($this->song_lowest_note, $perfect_offset),
			$this->nc->transposeNote($this->song_highest_note, $perfect_offset)
		);

		// If the perfect tone is the same as in the book, return 0.
		// We do % 12 because octaves are not considered.
		if (0 == $perfect_offset % 12)
		{
			$perfectTransposition->setAsBook(true);
		}

		// Store for further use.
		return $this->perfectTransposition = $perfectTransposition;
	}

	/**
	 * Find equivalent transpositions using capo.
	 *
	 * The algorithm in findPerfectTransposition() would be enough to get the
	 * "perfect" transposition, but there is one problem still: the "perfect"
	 * chords can be very weird to play, like D#, G#, etc. To overcome
	 * this, after calculating the perfect transposition, we will calculate
	 * equivalent transpositions using the capo, looking for the easiest chords.
	 *
	 * The criteria for which chords are easier or harder are implemented in
	 * Transposition::setScore().
	 * 
	 * @param  Transposition $transposition A given transposition without capo.
	 * @return array Array of <Transposition> with capo from 1 to 5.
	 */
	function findEquivalentsWithCapo(Transposition $transposition)
	{
		$withCapo = array();

		for ($i = 1; $i < 6; $i++)
		{
			$transposedChords = $this->nc->transposeChords($transposition->chords, $i * (-1));

			$withCapo[$i] = new Transposition(
				$transposedChords,
				$i,
				($transposedChords == $this->original_chords),
				$transposition->offset,
				$transposition->lowestNote,
				$transposition->highestNote
			);
		}

		return $withCapo;
	}

	/**
	 * Find alternative NOT-equivalent, but near (up to 1 semitone up or down) transpositions.
	 * 
	 * @return Transposition A non-equivalent transposition (yes, only one).
	 */
	function findAlternativeNotEquivalent()
	{
		$near_transpositions = array();

		$perfectTransposition = $this->getPerfectTransposition();

		foreach ($this->offsetsNotEquivalent as $dif)
		{
			$near = new Transposition(
				$this->nc->transposeChords($perfectTransposition->chords, $dif),
				0,
				false,
				$perfectTransposition->offset + $dif,
				$this->nc->transposeNote($perfectTransposition->lowestNote, $dif),
				$this->nc->transposeNote($perfectTransposition->highestNote, $dif),
				$dif
			);

			if ($this->first_chord_is_tone)
			{
				$near->setAlternativeChords($this->nc);
			}

			if ($this->original_chords == $near->chords)
			{
				$near->setAsBook(true);
			}

			//If it's too low or too high, discard it
			if ($this->nc->distanceWithOctave($near->lowestNote, $this->singer_lowest_note) < 0)
			{
				continue;
			}

			if ($this->nc->distanceWithOctave($near->highestNote, $this->singer_highest_note) > 0)
			{
				continue;
			}

			//If it's not better than the best of the "perfects", discard it
			$perfectAndEquivalent = $this->getTranspositions();
			if ($perfectAndEquivalent[0]->score <= $near->score)
			{
				continue;
			}

			$near_transpositions[] = $near;
		}

		$not_equivalent = $this->sortTranspositionsByEase($near_transpositions);
		return (!empty($not_equivalent)) ? $not_equivalent[0] : null;
	}

	/**
	 * Sorts an array of Transpositions from easiest to hardest.
	 * 
	 * @param  array $transpositions Array of Transpositions, with the score already set.
	 * @return array The sorted array
	 */
	function sortTranspositionsByEase(array $transpositions)
	{
		usort($transpositions, function($one, $two) {
			return ($one->score < $two->score) ? -1 : 1;
		});

		return $transpositions;
	}

	/**
	 * Main method to be used by the clients of this class. It returns the
	 * perfect and equivalent transpositions for a given song, sorted by ease.
	 * 
	 * @param 	integer $limitTranspositions Limit of equivalent transpositions to return
	 * @param 	boolean $forceHighestNote Used only in wizard to find highest note.
	 * @param 	boolean $forceLowestNote Used only in wizard to find lowest note.
	 * @return 	array 	Array of Transposition objects, sorted by chord ease.
	 */
	function getTranspositions($limitTranspositions=2, $forceHighestNote=false, $forceLowestNote=false)
	{
		if (empty($this->perfectAndEquivalent))
		{
			$perfectTransposition = $this->getPerfectTransposition($forceHighestNote, $forceLowestNote);

			$equivalents = $this->findEquivalentsWithCapo($perfectTransposition, $this->original_chords);

			$perfect_and_equivalent = array_merge(array($perfectTransposition), $equivalents);
			$perfect_and_equivalent = $this->sortTranspositionsByEase($perfect_and_equivalent);

			$this->perfectAndEquivalent = $perfect_and_equivalent;
		}

		//This shouldn't be done before to avoid conflicts
		foreach ($this->perfectAndEquivalent as &$transposition)
		{
			if ($this->first_chord_is_tone)
			{
				$transposition->setAlternativeChords($this->nc);
			}
		}

		return ($limitTranspositions)
			? array_slice($this->perfectAndEquivalent, 0, $limitTranspositions)
			: $this->perfectAndEquivalent;
	}
}