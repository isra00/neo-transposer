<?php

namespace NeoTransposer\Model;

/**
 * Core algorithm for transposing songs. It implementing three types of transpositions: 
 *
 * - centered: the song voice range is transposed to the center of singer's voice range.
 * - equivalentsWithCapo: transpositions equivalent to the centered using capo 1 to 5, searching one whose chords are easier than the centered.
 * - notEquivalent: transpose the centered ±1 and get its equivalents with capo, searching one whose chords are easier than the centered and the equivalentsWithCapo.
 * - peopleCompatible: transposition that is within the singer's voice range but also withing the people's voice range in the parts of the song that are sung by the people. Additional data is required (people_lowest_note, people_highest_note) for each song.
 * 
 * Additionally, after transposing the chords, some chords that are difficult to
 * beginners are replaced by others somehow equivalent, like B7 instead of B. This
 * is only done if the song data has the flag firstChordIsKey enabled.
 * 
 * The flag forceVoiceLimit is not used in real life transpositions, but only in
 * the Empiric Wizard to force the singer to use the lowest or highest voice.
 */
class AutomaticTransposer extends \NeoTransposer\AppAccess
{
	const FORCE_LOWEST  = 1;
	const FORCE_HIGHEST = 2;

	/**
	 * @var NotesCalculator
	 */
	protected $nc;

	protected $singerLowestNote;
	protected $singerHighestNote;
	protected $songLowestNote;
	protected $songHighestNote;
	protected $originalChords;
	protected $firstChordIsKey;
	protected $songPeopleLowestNote;
	protected $songPeopleHighestNote;

	/**
	 * The calculated centered transposition.
	 * @var Transposition
	 */
	protected $centeredTransposition;

	/**
	 * The calculated centered and equivalent transpositions, sorted by ease.
	 * @var array
	 */
	protected $centeredAndEquivalent;

	/**
	 * Offsets (in semitones) from the centered transposition, used for
	 * searching nonEquivalent transpositions.
	 * 
	 * @var array
	 */
	protected $offsetsNotEquivalent = array(-1, 1);

	/**
	 * Constructor needs all the data to calculate the transpositions.
	 * 
	 * @param	string	$singerLowestNote	Singer's lowest note
	 * @param	string	$singerHighestNote	Singer's highest note
	 * @param	string	$songLowestNote		Song's lowest note
	 * @param	string	$songHighestNote	Song's highest note
	 * @param	array	$originalChords		Song original chords
	 */
	function setTransposerData($singerLowestNote, $singerHighestNote, $songLowestNote, $songHighestNote, $originalChords, $firstChordIsKey, $songPeopleHighestNote, $songPeopleLowestNote)
	{
		$this->singerLowestNote	 = $singerLowestNote;
		$this->singerHighestNote = $singerHighestNote;
		$this->songLowestNote	 = $songLowestNote;
		$this->songHighestNote	 = $songHighestNote;
		$this->originalChords	 = $originalChords;
		$this->firstChordIsKey	 = $firstChordIsKey;
		$this->songPeopleHighestNote = $songPeopleHighestNote;
		$this->songPeopleLowestNote  = $songPeopleLowestNote;

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
	 * @param  int 				$forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return Transposition 	The transposition matching that voice.
	 */
	public function calculateCenteredTransposition($forceVoiceLimit=null)
	{
		if (!empty($this->centeredTransposition))
		{
			return $this->centeredTransposition;
		}

		$songWideness = $this->nc->distanceWithOctave(
			$this->songHighestNote, 
			$this->songLowestNote
		);
		
		$singerWideness = $this->nc->distanceWithOctave(
			$this->singerHighestNote, 
			$this->singerLowestNote
		);

		/*
		 * The song is located in the center of singer's range, but if middle is
		 * not an integer (odd number), it will be rounded up. If the song's 
		 * range is wider than the singer's, it will be located in the bottom, 
		 * so that notes exceeding notes will be high. We do this because when 
		 * it happens, the singer can sing those notes one octave down; as well 
		 * as when forceVoiceLimit is FORCE_LOWEST.
		 */
		$offsetFromSingerLowest = ($songWideness >= $singerWideness)
			? 0
			: round(($singerWideness - $songWideness) / 2);

		//This will transpose the song in the lowest or highest limit of the singer's range
		if ($forceVoiceLimit)
		{
			$offsetFromSingerLowest = ($forceVoiceLimit == self::FORCE_HIGHEST) 
				? ($singerWideness - $songWideness) 
				: 0;
		}

		$centeredOffset = intval(
			(-1) * $this->nc->distanceWithOctave($this->songLowestNote, $this->singerLowestNote)
			+ $offsetFromSingerLowest
		);

		$centeredTransposition = $this->app['new.Transposition']->setTranspositionData(
			$this->nc->transposeChords($this->originalChords, $centeredOffset),
			0,
			false,
			$centeredOffset,
			$this->nc->transposeNote($this->songLowestNote, $centeredOffset),
			$this->nc->transposeNote($this->songHighestNote, $centeredOffset),
			null
		);

		// If the centered key is the same as in the book, return 0.
		// We do % 12 because octaves are not considered.
		if (0 == $centeredOffset % 12)
		{
			$centeredTransposition->setAsBook(true);
		}

		// Store for further use.
		return $this->centeredTransposition = $centeredTransposition;
	}

	/**
	 * Find equivalent transpositions using capo.
	 *
	 * The algorithm in findCenteredTransposition() would be enough to get the
	 * "centered" transposition, but there is one problem still: the "centered"
	 * chords can be very weird to play, like D#, G#, etc. To overcome
	 * this, after calculating the centered transposition, we will calculate
	 * equivalent transpositions using the capo, looking for the easiest chords.
	 *
	 * The criteria for which chords are easier or harder are implemented in
	 * Transposition::setScore().
	 * 
	 * @param  Transposition $transposition A given transposition without capo.
	 * @return array Array of <Transposition> with capo from 1 to 5.
	 */
	public function calculateEquivalentsWithCapo(Transposition $transposition)
	{
		$withCapo = array();

		for ($i = 1; $i < 6; $i++)
		{
			$transposedChords = $this->nc->transposeChords($transposition->chords, $i * (-1));

			$withCapo[$i] = $this->app['new.Transposition']->setTranspositionData(
				$transposedChords,
				$i,
				($transposedChords == $this->originalChords),
				$transposition->offset,
				$transposition->lowestNote,
				$transposition->highestNote,
				$transposition->deviationFromCentered
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
		usort($transpositions, function(Transposition $one, Transposition $two) {
			return $one->score <=> $two->score;
		});

		return $transpositions;
	}

	/**
	 * Main method to be used by the clients of this class. It returns the
	 * centered and equivalent transpositions for a given song, sorted by ease.
	 * 
	 * @param	integer $limitTranspositions Limit of equivalent transpositions to return
	 * @param	int $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return	array 	Array of Transposition objects, sorted by chord ease.
	 */
	public function getTranspositions($limitTranspositions=2, $forceVoiceLimit=false)
	{
		if (empty($this->centeredAndEquivalent))
		{
			$centeredTransposition = $this->calculateCenteredTransposition($forceVoiceLimit);
			$equivalents = $this->calculateEquivalentsWithCapo($centeredTransposition, $this->originalChords);

			$centeredAndEquivalent = array_merge(array($centeredTransposition), $equivalents);
			$centeredAndEquivalent = $this->sortTranspositionsByEase($centeredAndEquivalent);

			$this->centeredAndEquivalent = $centeredAndEquivalent;
		}

		//This shouldn't be done before to avoid conflicts
		foreach ($this->centeredAndEquivalent as &$transposition)
		{
			if ($this->firstChordIsKey)
			{
				$transposition->setAlternativeChords($this->nc);
			}
		}

		//If alternative chords have been set, scores may change and so positions.
		$this->centeredAndEquivalent = $this->sortTranspositionsByEase($this->centeredAndEquivalent);

		return ($limitTranspositions)
			? array_slice($this->centeredAndEquivalent, 0, $limitTranspositions)
			: $this->centeredAndEquivalent;
	}

	/**
	 * Find one surrounding (-1 / +1 semitone) transposition with easier chords.
	 * If calculates also its equivalents with capo.
	 * 
	 * @return Transposition A non-equivalent transposition (yes, only one).
	 */
	public function calculateAlternativeNotEquivalent()
	{
		$nearTranspositions = $this->calculateSurroundingTranspositions(
			$this->offsetsNotEquivalent,
			$this->getTranspositions()[0]->score
		);

		if (!empty($nearTranspositions))
		{
			$notEquivalentSorted = $this->sortTranspositionsByEase($nearTranspositions);

			//For algorithm conservatism, no-capo takes always precedence.
			foreach ($notEquivalentSorted as $transposition)
			{
				if (0 == $transposition->getCapo())
				{
					$notEquivalentSorted[0] = $transposition;
				}
			}

			return $notEquivalentSorted[0];
		}
	}

	/**
	 * Get transpositions higher and lower than the centered.
	 * 
	 * @param array		$range				The range in semitones, e.g. [-2, -1]
	 * @param integer	$maxScore			Return only transpositions with score lower than this.
	 * @param integer	$reduceSingerLimits	Singer's voice range will be reduced in the top and bottom by this number of semitones.
	 * @return array	An array of Transposition objects.
	 */
	protected function calculateSurroundingTranspositions($range, $maxScore, $reduceSingerLimits=false)
	{
		$centeredTransposition = $this->calculateCenteredTransposition();

		$nearTranspositions = array();

		foreach ($range as $dif)
		{
			$near = $this->app['new.Transposition']->setTranspositionData(
				$this->nc->transposeChords($centeredTransposition->chords, $dif),
				0,
				false,
				$centeredTransposition->offset + $dif,
				$this->nc->transposeNote($centeredTransposition->lowestNote, $dif),
				$this->nc->transposeNote($centeredTransposition->highestNote, $dif),
				$dif
			);

			$nearAndItsEquivalentsWithCapo = $this->sortTranspositionsByEase(
				array_merge(
					array($near),
					$this->calculateEquivalentsWithCapo($near)
				)
			);

			foreach ($nearAndItsEquivalentsWithCapo as $notEquivalent)
			{

				if ($this->originalChords == $notEquivalent->chords)
				{
					$notEquivalent->setAsBook(true);
				}

				if ($this->firstChordIsKey)
				{
					$notEquivalent->setAlternativeChords($this->nc);
				}

				//If it's too low or too high, discard it
				if ($this->nc->distanceWithOctave($notEquivalent->lowestNote, $this->singerLowestNote) < 0)
				{
					continue;
				}

				if ($this->nc->distanceWithOctave($notEquivalent->highestNote, $this->singerHighestNote) > 0)
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
				 * with capo replacing the one without capo if score is lower.
				 * Another way of soft-deploying is, after filling the DB with
				 * the artistic offset, if the non-equivalent is as the artistic
				 * requires (higher or lower), showing it as the first choice
				 * (before the centered transposition).
				 */
				if ($notEquivalent->getCapo())
				{
					//continue;
				}

				$nearTranspositions[] = $notEquivalent;
			}
		}

		return $nearTranspositions;
	}

	function setSongPeopleRange($songPeopleHighestNote, $songPeopleLowestNote)
	{
		$this->songPeopleHighestNote = $songPeopleHighestNote;
		$this->songPeopleLowestNote  = $songPeopleLowestNote;
	}

	function calculatePeopleCompatible()
	{
		if (empty($this->songPeopleLowestNote))
		{
			//var_dump("No hay people data");
			return;
		}

		// @todo Crear una clase VoiceRange y usarla en todas partes?
		$peopleRange = ['lowest' => 'B1', 'highest' => 'B2'];

		$centeredTransposition = $this->calculateCenteredTransposition();

		$centeredForPeopleRange = [
			'lowest'  => $this->nc->transposeNote($this->songPeopleLowestNote, $centeredTransposition->offset),
			'highest' => $this->nc->transposeNote($this->songPeopleHighestNote, $centeredTransposition->offset)
		];
	
		if ($this->centeredTranspositionIsWithinPeopleRange($centeredForPeopleRange, $peopleRange))
		{
			//var_dump("No hace falta porque ya está en el people range");
			return;
		}

		$peopleDistanceToLimit = $this->nc->distanceWithOctave(
			$peopleRange['highest'], 
			$centeredForPeopleRange['highest']
		);

		$offsetForPeople = $centeredTransposition->offset + $peopleDistanceToLimit;

		$singerRangeApplyingOffsetForPeople = [
			'lowest'  => $this->nc->transposeNote($this->songLowestNote,  $offsetForPeople),
			'highest' => $this->nc->transposeNote($this->songHighestNote, $offsetForPeople)
		];

		if ($this->nc->distanceWithOctave($this->singerHighestNote, $singerRangeApplyingOffsetForPeople['highest']) < 0)
		{
			//var_dump("La people resulta demasiado alta para el cantor");
			return;
		}

		if ($this->nc->distanceWithOctave($singerRangeApplyingOffsetForPeople['highest'], $this->singerLowestNote) < 0)
		{
			//var_dump("La people resulta demasiado baja para el cantor");
			return;
		}

		$peopleCompatibleTransposition = $this->app['new.PeopleCompatibleTransposition']->setTranspositionData(
			$this->nc->transposeChords($this->originalChords, $offsetForPeople),
			0,
			($offsetForPeople == 0),
			$offsetForPeople,
			$singerRangeApplyingOffsetForPeople['lowest'],
			$singerRangeApplyingOffsetForPeople['highest'],
			$peopleDistanceToLimit
		);

		$peopleCompatibleTransposition->peopleLowestNote = $this->nc->transposeNote($centeredForPeopleRange['lowest'],  $peopleDistanceToLimit);
		$peopleCompatibleTransposition->peopleHighestNote = $this->nc->transposeNote($centeredForPeopleRange['highest'], $peopleDistanceToLimit);

		return $peopleCompatibleTransposition;
	}

	protected function centeredTranspositionIsWithinPeopleRange($centeredForPeopleRange, $peopleRange)
	{
		return ($this->nc->distanceWithOctave($centeredForPeopleRange['highest'], $peopleRange['highest']) < 0)
			&& ($this->nc->distanceWithOctave($peopleRange['lowest'], $centeredForPeopleRange['lowest']) < 0);
	}
}