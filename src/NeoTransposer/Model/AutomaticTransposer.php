<?php

namespace NeoTransposer\Model;

/**
 * Core algorithm for transposing songs. It implements four types of transpositions:
 *
 * 1) centered: the song voice range is transposed to the center of singer's voice range.
 * 2) equivalentsWithCapo: transpositions equivalent to the centered using capo 1 to 5, 
 *    searching one whose chords are easier than the centered.
 * 3) notEquivalent: transpose the centered ±1 and get its equivalents with capo, 
 *    searching one whose chords are easier than the centered and the equivalentsWithCapo.
 * 4) peopleCompatible: transposition that is within the singer's voice range but 
 *    also within the people's voice range in the parts of the song that are sung 
 *    by the people. Additional data is required (people_lowest_note, 
 *    people_highest_note) for each song.
 * 
 * Additionally, after transposing the chords, some chords that are difficult to
 * beginners are replaced by others somehow equivalent, like B7 instead of B. This
 * is only done if the song data has the flag firstChordIsKey enabled.
 * 
 * The flag forceVoiceLimit is not used in real life transpositions, but only in
 * the Empiric Wizard to force the singer to use the lowest or highest voice.
 *
 * @todo Refactor this class to make it more Single-Responsibility. Lo de "Automatic" sobra: TransposerAlgorithm o mejor Transposer
 */
class AutomaticTransposer extends \NeoTransposer\AppAccess
{
	const FORCE_LOWEST  = 1;
	const FORCE_HIGHEST = 2;

	/**
	 * @var NotesCalculator
	 */
	protected $notesCalculator;

	/**
	 * @type NotesRange
	 */
	protected $singerRange;

	/**
	 * @type NotesRange
	 */
	protected $songRange;

	/**
	 * @type array
	 */
	protected $originalChords;

	/**
	 * @type boolean
	 */
	protected $firstChordIsKey;

	/**
	 * @type NotesRange
	 */
	protected $songPeopleRange;

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
	const OFFSETS_NOT_EQUIVALENT = [-1, 1];

	/**
	 * Set all data needed to calculate the transpositions.
	 * 
	 * @param	NotesRange	$singerRange		Singer's voice range
	 * @param	NotesRange	$songRange			Song's voice range
	 * @param	array		$originalChords		Song original chords
	 * @param	boolean		$firstChordIsKey	Song original chords
	 * @param	NotesRange	$songPeopleRange	Song's voice range for people
	 */
	public function setTransposerData(NotesRange $singerRange, NotesRange $songRange, $originalChords, $firstChordIsKey, NotesRange $songPeopleRange=null)
	{
		$this->singerRange	 	= $singerRange;
		$this->songRange	 	= $songRange;
		$this->originalChords	= $originalChords;
		$this->firstChordIsKey	= $firstChordIsKey;
		$this->songPeopleRange	= $songPeopleRange;

		$this->notesCalculator				= new NotesCalculator;
	}

	/**
	 * This is the core algorithm for Automatic transposition.
	 *
	 * Given the the lowest and highest note of the singer and of the song, the 
	 * algorithm transposes the song locating its range in the middle of the
	 * singer's voice range through simple arithmetics: calculate the offset 
	 * between the original song's lowest note and the centered position, and 
	 * then, transpose each chord using that offset.
	 * 
	 * @param int $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return Transposition 	The transposition matching that voice.
	 */
	public function calculateCenteredTransposition(?int $forceVoiceLimit=0)
	{
		if (!empty($this->centeredTransposition))
		{
			return $this->centeredTransposition;
		}

		$songWideness 	= $this->notesCalculator->rangeWideness($this->songRange);
		$singerWideness	= $this->notesCalculator->rangeWideness($this->singerRange);

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
			(-1) * $this->notesCalculator->distanceWithOctave($this->songRange->lowest, $this->singerRange->lowest)
			+ $offsetFromSingerLowest
		);

		$centeredTransposition = $this->app['new.Transposition']->setTranspositionData(
			$this->notesCalculator->transposeChords($this->originalChords, $centeredOffset),
			0,
			false,
			$centeredOffset,
			new NotesRange(
				$this->notesCalculator->transposeNote($this->songRange->lowest, $centeredOffset),
				$this->notesCalculator->transposeNote($this->songRange->highest, $centeredOffset)
			),
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
	 * @param 	Transposition	$transposition	A given transposition without capo.
	 * @param	string			$dcFactory		Dependency Container factory for constructing Transposition objects
	 * @return	array			Array of <Transposition> with capo from 1 to 5.
     *
     * @todo Refactor dcFactory makes no sense. Either $app or the factory itself (Callable)
	 */
	public function calculateEquivalentsWithCapo(Transposition $transposition, string $dcFactory='new.Transposition')
	{
		$withCapo = [];

		for ($i = 1; $i < 6; $i++)
		{
			$transposedChords = $this->notesCalculator->transposeChords($transposition->chords, $i * (-1));

			$withCapo[$i] = $this->app[$dcFactory]->setTranspositionData(
				$transposedChords,
				$i,
				($transposedChords == $this->originalChords),
				$transposition->offset,
				$transposition->range,
				$transposition->deviationFromCentered
			);
		}

		return $withCapo;
	}

	/**
	 * Sorts an array of Transpositions from lowest to highest score.
	 * If two have same score but one is asBook, that one takes precedence.
	 * 
	 * @param  array $transpositions Array of Transpositions, with the score already set.
	 * @return array The sorted array
	 */
	public function sortTranspositionsByEase(array $transpositions)
	{
		usort($transpositions, function(Transposition $one, Transposition $two) {

			//If both have same score but one is asBook, that one goes first.
			if ($one->score == $two->score)
			{
				return ($two->getAsBook()) ? 1 : 0;
			}

			return ($one->score < $two->score) ? -1 : 1;
		});

		return $transpositions;
	}

	/**
	 * Main method to be used by the clients of this class. It returns the
	 * centered and equivalent transpositions for a given song, sorted by ease.
	 * 
	 * @param int $limitTranspositions Limit of equivalent transpositions to return
	 * @param int|null $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 * @return	array 	Array of Transposition objects, sorted by chord ease.
	 */
	public function getTranspositions(?int $limitTranspositions=2, ?int $forceVoiceLimit=0)
	{
		if (empty($this->centeredAndEquivalent))
		{
			$centeredTransposition = $this->calculateCenteredTransposition($forceVoiceLimit);
			$equivalents = $this->calculateEquivalentsWithCapo($centeredTransposition);

			$centeredAndEquivalent = array_merge(array($centeredTransposition), $equivalents);
			$centeredAndEquivalent = $this->sortTranspositionsByEase($centeredAndEquivalent);

			$this->centeredAndEquivalent = $centeredAndEquivalent;
		}

		//This shouldn't be done before to avoid conflicts
		foreach ($this->centeredAndEquivalent as &$transposition)
		{
			if ($this->firstChordIsKey)
			{
				$transposition->setAlternativeChords($this->notesCalculator);
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
			self::OFFSETS_NOT_EQUIVALENT,
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

		$nearTranspositions = [];

		foreach ($range as $dif)
		{
			$near = $this->app['new.Transposition']->setTranspositionData(
				$this->notesCalculator->transposeChords($centeredTransposition->chords, $dif),
				0,
				false,
				$centeredTransposition->offset + $dif,
				new NotesRange(
					$this->notesCalculator->transposeNote($centeredTransposition->range->lowest, $dif),
					$this->notesCalculator->transposeNote($centeredTransposition->range->highest, $dif)
				),
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
					$notEquivalent->setAlternativeChords($this->notesCalculator);
				}

				//If it's too low or too high, discard it
				if ($this->notesCalculator->distanceWithOctave($notEquivalent->range->lowest, $this->singerRange->lowest) < 0)
				{
					continue;
				}

				if ($this->notesCalculator->distanceWithOctave($notEquivalent->range->highest, $this->singerRange->highest) > 0)
				{
					continue;
				}

				if ($maxScore <= $notEquivalent->score)
				{
					continue;
				}

				$nearTranspositions[] = $notEquivalent;
			}
		}

		return $nearTranspositions;
	}

	/**
	 * Calculate transposition compatible with a standard range of people.
	 * 
	 * In this algorithm six cases may occur:
	 * 1) No data in DB for the people range of the song: no adjustment done.
	 * 2) The centeredTransposition already falls within people's range: no adjustment done.
	 * 3) The song's range is wider than singer's range: no adjustment done.
	 * 4) peopleSong range is wider or equal than people's range: move it (as 
	 *    far as the singer's range allows) to the people range's bottom, so 
	 *    that the excess is put in the highest notes. It is the same design 
	 *    decision as in CenteredTransposition when the song's range > singer's 
	 *    range.
	 * 5) The centered transposition is too low for the people: it is raised up
	 *    until peopleSong.lowest = people.lowest, but limited by the singer's
	 *    range (i.e., singer.highest =< song.highest)
	 * 6) The centered transposition is too high for the people: it is lowered
	 *    down until peopleSong.highest = people.highest, but limited by the
	 *    singer's range. This case and the former one presuppose that 
	 *    peopleSong range is NOT wider than people's range.
	 * 
	 * The case that occured is reported in the returned PeopleCompatibleCalculation::$status
	 * Calculations (offsets) are done based on the centeredTransposition.
	 * This algorithm does not deal with the relation of this transposition
	 * with others (i.e. hiding notEquivalent when there is peopleCompatible, etc.)
	 */
	public function calculatePeopleCompatible() : PeopleCompatibleCalculation
	{
		// 1) No data in DB for the people range of the song: nothing is done
		if (empty($this->songPeopleRange))
		{
			return new PeopleCompatibleCalculation(PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA);
		}

		$peopleRange 			= new NotesRange($this->app['neoconfig']['people_range'][0], $this->app['neoconfig']['people_range'][1]);
		$centeredTransposition	= $this->calculateCenteredTransposition();
		$status					= null;

		$peopleRangeInCentered	= new NotesRange(
			$this->notesCalculator->transposeNote($this->songPeopleRange->lowest, $centeredTransposition->offset),
			$this->notesCalculator->transposeNote($this->songPeopleRange->highest, $centeredTransposition->offset)
		);
	
		// 2) The centeredTransposition already falls within people's range.
		if ($peopleRangeInCentered->isWithinRange($peopleRange, $this->notesCalculator))
		{
			return new PeopleCompatibleCalculation(PeopleCompatibleCalculation::ALREADY_COMPATIBLE);
		}

		// 3) The song's range is wider than singer's range: do nothing.
		if ($this->notesCalculator->rangeWideness($this->songRange) >= $this->notesCalculator->rangeWideness($this->singerRange))
		{
			return new PeopleCompatibleCalculation(PeopleCompatibleCalculation::WIDER_THAN_SINGER);
		}

		$fromPeopleLowestInCenteredToPeopleLowest = $this->notesCalculator->distanceWithOctave(
			$peopleRange->lowest,
			$peopleRangeInCentered->lowest
		);
		
		$fromSingerLowestCenteredToSingerLowest   = $this->notesCalculator->distanceWithOctave(
			$this->singerRange->lowest,
			$this->centeredTransposition->range->lowest
		);

		$fromSingerHighestCenteredToSingerHighest = $this->notesCalculator->distanceWithOctave(
			$this->singerRange->highest,
			$this->centeredTransposition->range->highest
		);

		// 4) peopleSong range is wider than people's range.
		if ($this->notesCalculator->rangeWideness($this->songPeopleRange) > $this->notesCalculator->rangeWideness($peopleRange))
		{
			//If lowering, limit with singer's lowest. If raising, with highest.
			$singerLimit = ($fromPeopleLowestInCenteredToPeopleLowest < 0)
				? $fromSingerLowestCenteredToSingerLowest
				: $fromSingerHighestCenteredToSingerHighest;

			$offsetFromCentered = min(
				abs($singerLimit),
				abs($fromPeopleLowestInCenteredToPeopleLowest)
			);

			if ($fromPeopleLowestInCenteredToPeopleLowest < 0)
			{
				//When lowering, invert because of abs() above.
				$offsetFromCentered = $offsetFromCentered * (-1);
			}

			if (0 == $offsetFromCentered)
			{
				return new PeopleCompatibleCalculation(PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER);
			}

			return $this->createPeopleCompatibleCalculation(
				PeopleCompatibleCalculation::ADJUSTED_WIDER,
				$offsetFromCentered,
				$peopleRangeInCentered
			);
		}

		// 5) The centered transposition is too low for the people
		if ($fromPeopleLowestInCenteredToPeopleLowest > 0)
		{
			$offsetFromCentered = min(
				abs($fromPeopleLowestInCenteredToPeopleLowest),
				abs($fromSingerHighestCenteredToSingerHighest)
			);

			$status = (abs($offsetFromCentered) < abs($fromPeopleLowestInCenteredToPeopleLowest))
				? PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE
				: PeopleCompatibleCalculation::ADJUSTED_WELL;

			return $this->createPeopleCompatibleCalculation(
				$status,
				$offsetFromCentered,
				$peopleRangeInCentered
			);
		}

		$fromPeopleHighestInCenteredToPeopleHighest = $this->notesCalculator->distanceWithOctave(
			$peopleRange->highest,
			$peopleRangeInCentered->highest
		);

		// 6) The centered transposition is too high for the people
		if ($fromPeopleHighestInCenteredToPeopleHighest < 0)
		{
			$offsetFromCentered = min(
				abs($fromPeopleHighestInCenteredToPeopleHighest),
				abs($fromSingerLowestCenteredToSingerLowest)
			) * (-1);

			$status = (abs($offsetFromCentered) < abs($fromPeopleHighestInCenteredToPeopleHighest))
				? PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE
				: PeopleCompatibleCalculation::ADJUSTED_WELL;

			return $this->createPeopleCompatibleCalculation(
				$status, 
				$offsetFromCentered,
				$peopleRangeInCentered
			);
		}

		throw new \Exception("This should never happen.");
	}

	/**
	 * Create a PeopleCompatibleCalculation object given the status and the
	 * offset from the centered transposition
	 * 
	 * @param	int			$status					One of PeopleCompatibleCalculation's constants.
	 * @param	int			$offsetFromCentered		Offset of the peopleCompatible from centered.
	 * @param	NotesRange	$peopleRangeInCentered	Voice range of the people in centered transposition.
	 * 
	 * @return	PeopleCompatibleCalculation	The PeopleCompatibleCalculation object.
	 */
	protected function createPeopleCompatibleCalculation($status, $offsetFromCentered, NotesRange $peopleRangeInCentered) : PeopleCompatibleCalculation
	{
		$offsetFromOriginal = $this->centeredTransposition->offset + $offsetFromCentered;

		$peopleCompatibleTransposition = $this->app['new.PeopleCompatibleTransposition']->setTranspositionData(
			$this->notesCalculator->transposeChords($this->originalChords, $offsetFromOriginal),
			0,
			($offsetFromOriginal == 0),
			$offsetFromOriginal,
			$this->notesCalculator->transposeRange($this->songRange, $offsetFromOriginal),
			$offsetFromCentered
		);

		$peopleCompatibleTransposition = $this->chooseEasiestEquivalentWithCapo($peopleCompatibleTransposition, 'new.PeopleCompatibleTransposition');

		$peopleCompatibleTransposition->peopleRange	= $this->notesCalculator->transposeRange($peopleRangeInCentered, $offsetFromCentered);

		return new PeopleCompatibleCalculation($status, $peopleCompatibleTransposition);
	}

	/**
	 * Given a transposition, calculate its equivalents with capo and return the
	 * easiest one.
	 * 
	 * @param Transposition	$transposition	The given transposition, with capo 0.
	 * @param string		$dcFactory		Dependency Container factory for constructing Transposition objects
	 */
	protected function chooseEasiestEquivalentWithCapo(Transposition $transposition, string $dcFactory='new.Transposition') : Transposition
	{
		$equivalentsWithCapo = array_merge(
			array($transposition),
			$this->calculateEquivalentsWithCapo($transposition, $dcFactory)
		);

		foreach ($equivalentsWithCapo as &$trans)
		{
			if ($this->firstChordIsKey)
			{
				$trans->setAlternativeChords($this->notesCalculator);
			}
		}
		
		return $this->sortTranspositionsByEase($equivalentsWithCapo)[0];
	}
}
