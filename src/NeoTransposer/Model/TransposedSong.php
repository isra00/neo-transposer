<?php

namespace NeoTransposer\Model;

use \NeoTransposer\NeoApp;
use \NeoTransposer\Persistence\SongPersistence;
use \NeoTransposer\Model\Song;

/**
 * Read a song from DB, calculate its transpositions and prepare for print.
 *
 * This class is in an upper level than AutomaticTransposer and is intended to
 * be used by controllers such as TransposeSong and WizardEmpiric.
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
	 * perfect and equivalent transpositions for a given song, sorted by ease.
	 * 
	 * @param 	boolean $forceHighestSingerNote 	Used only in wizard to find highest note.
	 * @param 	boolean $forceLowestSingerNote 		Used only in wizard to find lowest note.
	 * @param 	boolean $overrideSongHighestNote 	Override song's highest note. Used in Wizard 
	 * 												because not the whole song is sung but only a 
	 * 												part that might not contain the song's highest 
	 * 												note registered in the DB.
	 */
	public function transpose($forceHighestSingerNote=false, $forceLowestSingerNote=false, $overrideSongHighestNote=null)
	{
		$transposer = new AutomaticTransposer(
			$this->app['neouser']->lowest_note,
			$this->app['neouser']->highest_note,
			$this->song->lowestNote,
			$overrideSongHighestNote ? $overrideSongHighestNote : $this->song->highestNote, 
			$this->song->originalChords,
			$this->song->firstChordIsTone
		);

		$this->transpositions = $transposer->getTranspositions(2, $forceHighestSingerNote, $forceLowestSingerNote);
		$this->not_equivalent = $transposer->findAlternativeNotEquivalent();

		$this->prepareForPrint();
	}

	/**
	 * Prepare transpositions for print (chords and capo sentence).
	 */
	public function prepareForPrint()
	{
		$printer = $this->app['chord_printers.get']($this->song->bookChordPrinter);

		$this->song->originalChords = $printer->printChordset($this->song->originalChords);

		foreach ($this->transpositions as &$transposition)
		{
			$transposition = $printer->printTransposition($transposition);
			$transposition->setCapoForPrint($this->app);
		}

		if ($this->not_equivalent)
		{
			$this->not_equivalent = $printer->printTransposition($this->not_equivalent);
			$this->not_equivalent->setCapoForPrint($this->app);
		}
	}
}