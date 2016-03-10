<?php

namespace NeoTransposer\Model;

/**
 * Transpose a given song automatically, given the singer's voice.
 * 
 * @todo Unify calculation method names: get/find/calculate...
 */
class AutomaticTransposer
{
	const FORCE_LOWEST  = 1;
	const FORCE_HIGHEST = 2;

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
	protected $first_chord_is_key;

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
	function __construct($singer_lowest_note, $singer_highest_note, $song_lowest_note, $song_highest_note, $original_chords, $first_chord_is_key)
	{
		$this->singer_lowest_note = $singer_lowest_note;
		$this->singer_highest_note = $singer_highest_note;
		$this->song_lowest_note = $song_lowest_note;
		$this->song_highest_note = $song_highest_note;
		$this->original_chords = $original_chords;
		$this->first_chord_is_key = $first_chord_is_key;

		$this->nc = new NotesCalculator;
	}

	/**
	 * This is the core algorithm for Automatic transposition.
	 *
	 * Given the the lowest and highest note of the singer and of the song, the 
	 * algorithm transposes the song locating its range in the middle of the
	 * singer's voice range through simple arithmetics: calculate the offset 
	 * between the original song's lowest note and the ideal position and 
	 * transpose each chord using that offset.
	 * 
	 * @todo Rename to "centered transposition". There is nothing perfect in this world.
	 * 
	 * @param  int 				$forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return Transposition 	The transposition matching that voice.
	 */
	public function getPerfectTransposition($forceVoiceLimit=false)
	{
		if (!empty($this->perfectTransposition))
		{
			return $this->perfectTransposition;
		}

		$song_wideness = $this->nc->distanceWithOctave(
			$this->song_highest_note, 
			$this->song_lowest_note
		);
		
		$singer_wideness = $this->nc->distanceWithOctave(
			$this->singer_highest_note, 
			$this->singer_lowest_note
		);

		/*
		 * The song is located in the center of singer's range, in order to be
		 * more comfortable. Note that when the middle 
		 * ((singer_wideness - song_wideness) / 2) is not an integer, it will be
		 * rounded up. If the song's range is wider than the singer's, it will
		 * be localted in the bottom, so that the exceeding notes will be high.
		 * We do this because when it happens, the singer can sing those notes
		 * one octave down. 
		 */
		$offset_from_singer_lowest = ($song_wideness >= $singer_wideness)
			? 0
			: round(($singer_wideness - $song_wideness) / 2);

		if ($forceVoiceLimit)
		{
			$offset_from_singer_lowest = ($forceVoiceLimit == self::FORCE_HIGHEST) ? ($singer_wideness - $song_wideness) : 0;
		}

		/*
		 * Given the offset_from_singer_lowest, now we calculate the offset for
		 * transposing the chords.
		 */
		$perfect_offset = intval(
			(-1) * $this->nc->distanceWithOctave($this->song_lowest_note, $this->singer_lowest_note)
			+ $offset_from_singer_lowest
		);

		$perfectTransposition = new Transposition(
			$this->nc->transposeChords($this->original_chords, $perfect_offset),
			0,
			false,
			$perfect_offset,
			$this->nc->transposeNote($this->song_lowest_note, $perfect_offset),
			$this->nc->transposeNote($this->song_highest_note, $perfect_offset)
		);

		// If the perfect key is the same as in the book, return 0.
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
	public function findEquivalentsWithCapo(Transposition $transposition)
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
				$transposition->highestNote,
				$transposition->deviationFromPerfect
			);
		}

		return $withCapo;
	}

	/**
	 * Sorts an array of Transpositions from easiest to hardest.
	 * 
	 * @param  array $transpositions Array of Transpositions, with the score already set.
	 * @return array The sorted array
	 */
	public function sortTranspositionsByEase(array $transpositions)
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
	 * @param  int $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return 	array 	Array of Transposition objects, sorted by chord ease.
	 */
	public function getTranspositions($limitTranspositions=2, $forceVoiceLimit=false)
	{
		if (empty($this->perfectAndEquivalent))
		{
			$perfectTransposition = $this->getPerfectTransposition($forceVoiceLimit);
			$equivalents = $this->findEquivalentsWithCapo($perfectTransposition, $this->original_chords);

			$perfect_and_equivalent = array_merge(array($perfectTransposition), $equivalents);
			$perfect_and_equivalent = $this->sortTranspositionsByEase($perfect_and_equivalent);

			$this->perfectAndEquivalent = $perfect_and_equivalent;
		}

		//This shouldn't be done before to avoid conflicts
		foreach ($this->perfectAndEquivalent as &$transposition)
		{
			if ($this->first_chord_is_key)
			{
				$transposition->setAlternativeChords($this->nc);
			}
		}

		//If alternative chords have been set, scores may change and so positions.
		$this->perfectAndEquivalent = $this->sortTranspositionsByEase($this->perfectAndEquivalent);

		return ($limitTranspositions)
			? array_slice($this->perfectAndEquivalent, 0, $limitTranspositions)
			: $this->perfectAndEquivalent;
	}

	/**
	 * Find surrounding (-1 / 1 semitone) transposition with easier chords.
	 * 
	 * @return Transposition A non-equivalent transposition (yes, only one).
	 */
	public function findAlternativeNotEquivalent()
	{
		$nearTranspositions = $this->getSurroundingTranspositions(
			$this->offsetsNotEquivalent,
			$this->getTranspositions()[0]->score
		);

		if (!empty($nearTranspositions))
		{
			$notEquivalent = $this->sortTranspositionsByEase($nearTranspositions);
			return $notEquivalent[0];
		}
	}

	/**
	 * Get transpositions higher or lower than the centered (perfect).
	 * 
	 * @param array		$range				The range in semitones, e.g. [-2, -1]
	 * @param integer	$maxScore			Return only transpositions with score lower than this.
	 * @param integer	$reduceSingerLimits	Singer's voice range will be reduced in the top and bottom by this number of semitones.
	 * @return array	An array of Transposition objects.
	 */
	protected function getSurroundingTranspositions($range, $maxScore, $reduceSingerLimits=false)
	{
		$perfectTransposition = $this->getPerfectTransposition();

		$nearTranspositions = array();

		foreach ($range as $dif)
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

			$nearAndItsEquivalentsWithCapo = $this->sortTranspositionsByEase(
				array_merge(
					array($near),
					$this->findEquivalentsWithCapo($near)
				)
			);

			foreach ($nearAndItsEquivalentsWithCapo as $notEquivalent)
			{

				if ($this->original_chords == $notEquivalent->chords)
				{
					$notEquivalent->setAsBook(true);
				}

				if ($this->first_chord_is_key)
				{
					$notEquivalent->setAlternativeChords($this->nc);
				}

				//If it's too low or too high, discard it
				if ($this->nc->distanceWithOctave($notEquivalent->lowestNote, $this->singer_lowest_note) < 0)
				{
					continue;
				}

				if ($this->nc->distanceWithOctave($notEquivalent->highestNote, $this->singer_highest_note) > 0)
				{
					continue;
				}

				if ($maxScore <= $notEquivalent->score)
				{
					continue;
				}

				/*
				 * Disable the non-equivalents with capo. Remove when ready to 
				 * deploy. How to deploy? First step, prefer always the 
				 * notEquivalent without capo. This way, no notEquivalent will
				 * change, but in some cases a notEquivalent with capo will be
				 * shown where no notEquivalent was being shown (because the
				 * notEquivalent had no lower score than the lowest centered).
				 * Second step, think and decide about offering notEquivalents 
				 * with capo replacing the one without capo if score is lower
				 */
				if ($notEquivalent->getCapo())
				{
					continue;
				}

				$nearTranspositions[] = $notEquivalent;
			}
		}

		return $nearTranspositions;
	}
}