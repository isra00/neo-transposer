<?php

namespace NeoTransposer\Model;

use \NeoTransposer\NeoApp;
use \NeoTransposer\Persistence\SongPersistence;
use \NeoTransposer\Model\Song;

/**
 * Read a song from DB, calculate its transpositions and prepare for print.
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
			$this->app['neouser']->lowest_note,
			$this->app['neouser']->highest_note,
			$this->song->lowestNote,
			$this->song->highestNote,
			$this->song->originalChords,
			$this->song->firstChordIsTone,
			$this->song->peopleHighestNote,
			$this->song->peopleLowestNote
		);

		$this->transpositions = $transposer->getTranspositions(2, $forceVoiceLimit);
		$this->not_equivalent = $transposer->findAlternativeNotEquivalent();
		
		if ($this->app['neoconfig']['people_compatible'])
		{
			$this->peopleCompatible = $transposer->getPeopleCompatible();
		}

		$this->prepareForPrint();

	}

	/**
	 * Prepare transpositions for print (chords and capo sentence).
	 */
	public function prepareForPrint()
	{
		$printer = $this->app['chord_printers.get']($this->song->bookChordPrinter);

		$this->song->originalChords = $printer->printChordset($this->song->originalChords);

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
}
