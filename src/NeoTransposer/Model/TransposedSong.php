<?php

namespace NeoTransposer\Model;

use \NeoTransposer\NeoApp;
use \NeoTransposer\Persistence\SongPersistence;
use \NeoTransposer\Model\Song;

/**
 * Read a song from DB, calculate its transpositions, sort them according to
 * some business logic and prepare for print.
 *
 * This class is in an upper level than AutomaticTransposer and is intended to
 * be used by controllers such as TransposeSong, AllSongsReport and WizardEmpiric.
 */
class TransposedSong
{
	/**
	 * @var NeoTransposer\Song
	 */
	public $song;

	/**
	 * @var Array
	 * @todo Rename to centered
	 */
	public $transpositions;

	/**
	 * @var Transposition
	 */
	public $not_equivalent;

	/**
	 * @var PeopleCompatibleTransposition
	 */
	public $peopleCompatible;

	/**
	 * One of PeopleCompatibleCalculation's constants.
	 * 
	 * @var int
	 */
	public $peopleCompatibleStatus;

	/**
	 * Only for debugging.
	 * 
	 * @var int
	 */
	public $peopleCompatibleStatusMsg;

	/**
	 * Whether the centered (and optionally not_equivalent) are compatible with 
	 * people range.
	 * 
	 * @var boolean
	 */
	public $isAlreadyPeopleCompatible;

	/**
	 * @var NeoTransposer\NeoApp;
	 */
	protected $app;

	protected function __construct(Song $song, NeoApp $app)
	{
		$this->song = $song;
		$this->app 	= $app;
	}

	/**
	 * Factory
	 * 
	 * @param  string|int            $id_song Song ID or slug.
	 * @param  \NeoTransposer\NeoApp $app     NeoApp instance.
	 * @return TransposedSong                 The created object.
	 */
	public static function create($idSong, NeoApp $app)
	{
		try
		{
			$song = SongPersistence::fetchSongById($idSong, $app['db']);
		}
		catch (\Exception $e)
		{
			$app->abort(404, $e->getMessage());
		}

		return new TransposedSong($song, $app);
	}


	/**
	 * Main method to be used by the clients of this class. It returns the
	 * centered and equivalent transpositions for a given song, sorted by ease.
	 * 
	 * @param  int $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
	 */
	public function transpose($forceVoiceLimit=null)
	{
		$transposer = $this->app['new.AutomaticTransposer'];
		
		$transposer->setTransposerData(
			$this->app['neouser']->range,
			$this->song->range,
			$this->song->originalChords,
			$this->song->firstChordIsTone,
			$this->song->peopleRange
		);

		$this->transpositions = $transposer->getTranspositions(2, $forceVoiceLimit);
		$this->not_equivalent = $transposer->calculateAlternativeNotEquivalent();

		if ($this->app['neoconfig']['people_compatible'])
		{
			$this->peopleCompatibleStuff($transposer);
		}

		//If there is notEquivalent, show only one centered.
		if ($this->not_equivalent && $this->app['neoconfig']['hide_second_centered_if_not_equivalent'])
		{
			unset($this->transpositions[1]);
		}

		$this->prepareForPrint();
	}

	/**
	 * Prepare transpositions for print (chords and capo sentence).
	 */
	public function prepareForPrint()
	{
		$printer = $this->app['chord_printers.get']($this->song->bookChordPrinter);

		$this->song->originalChordsForPrint = $printer->printChordset($this->song->originalChords);

		$transpositionsToPrint = array_merge(
			$this->transpositions, 
			[$this->not_equivalent, $this->peopleCompatible]
		);

		foreach ($transpositionsToPrint as &$transposition)
		{
			if (!empty($transposition))
			{
				$transposition = $printer->printTransposition($transposition);
			}
		}
	}

	/**
	 * Get the peopleCompatible transposition and decide whether the 
	 * notEquivalent (if any) should be shown.
	 */
	public function peopleCompatibleStuff(AutomaticTransposer $transposer)
	{
		$pcCalculation 					 = $transposer->calculatePeopleCompatible();
		$this->peopleCompatibleStatus 	 = $pcCalculation->status;
		$this->peopleCompatibleStatusMsg = $pcCalculation->getStatusMsg();
		$this->peopleCompatible 		 = $pcCalculation->peopleCompatibleTransposition;
		$this->isAlreadyPeopleCompatible = (PeopleCompatibleCalculation::ALREADY_COMPATIBLE == $pcCalculation->status);

		if (!$this->not_equivalent || !$this->peopleCompatible)
		{
			return;
		}

		//If Centered is already compatible but notEquivalent is not, then
		//remove notEquivalent. Otherwise the information we give to the user
		//"this transposition is already compatible" would be partially false.
		if ($this->isAlreadyPeopleCompatible && $this->not_equivalent)
		{
			if (!$this->isCompatibleWithPeople($this->not_equivalent, $pcCalculation))
			{
				$this->not_equivalent = null;
				return;
			}
		}

		//If there is notEquivalent and peopleCompatible, discard notEquivalent.
		if ($this->not_equivalent && $this->peopleCompatible)
		{
			$this->not_equivalent = null;
			return;
		}
	}

	/**
	 * Check whether the given transposition is within people's range for the current song.
	 */
	public function isCompatibleWithPeople(Transposition $transposition)
	{
		//No people data, no compatible.
		if (empty($this->song->peopleRange))
		{
			return;
		}

		$nc 			= new NotesCalculator;
		$peopleRange 	= new NotesRange($this->app['neoconfig']['people_range'][0], $this->app['neoconfig']['people_range'][1]);
		
		$peopleRangeInNotEquivalent = $nc->transposeRange(
			$this->song->peopleRange,
			$transposition->offset
		);
		
		return $peopleRangeInNotEquivalent->isWithinRange($peopleRange, $nc);
	}
}
