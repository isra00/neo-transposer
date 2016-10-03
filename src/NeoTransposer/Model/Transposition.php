<?php

namespace NeoTransposer\Model;

use \NeoTransposer\Model\NotesCalculator;
use \NeoTransposer\NeoApp;

/**
 * Represents a transposition of a song, with transported chords, capo, etc.
 */
class Transposition
{
	/**
	 * Transposed chords
	 * @var array
	 */
	public $chords = array();

	/**
	 * Transposed chords, after being processed by a ChordPrinter
	 * @var array
	 */
	public $chordsForPrint = array();
	
	/**
	 * Difficulty score
	 * @var integer
	 */
	public $score = 0;
	
	/**
	 * Capo number for the transposition
	 * @var integer
	 */
	protected $capo = 0;

	/**
	 * Capo number for the transposition, ready to be shown in the UI.
	 * @var string
	 */
	public $capoForPrint;

	/**
	 * Whether the transposition is the same as the original one.
	 * @var boolean
	 */
	protected $asBook = false;

	/**
	 * Offset used for transport from the original.
	 * @var integer
	 */
	public $offset = 0;

	/**
	 * Song's lowest note after transposing.
	 * @var string
	 */
	public $lowestNote;

	/**
	 * Song's highest note after transposing.
	 * @var string
	 */
	public $highestNote;

	/**
	 * Deviation from the centered transposition (in semitones).
	 * @var integer
	 */
	public $deviationFromCentered = 0;

	/**
	 * @var array
	 */
	public $scoresConfig;

	/**
	 * Used only for debug
	 * @var array
	 */
	public $scoreMap = array();

	public function __construct($chords=array(), $capo=0, $asBook=false, $offset=0, $lowest_note=null, $highest_note=null, $deviationFromCentered=0, array $scoresConfig)
	{
		$this->chords		= $chords;
		$this->capo			= $capo;
		$this->asBook 		= $asBook;
		$this->offset 		= $offset;
		$this->lowestNote 	= $lowest_note;
		$this->highestNote 	= $highest_note;
		$this->deviationFromCentered = $deviationFromCentered;
		$this->scoresConfig = $scoresConfig;

		$this->setScore();
	}

	/**
	 * Calculates the ease of the transposition, based on each chord's ease.
	 */
	public function setScore()
	{
		$this->score = 0;

		foreach ($this->chords as $chord)
		{
			$scoreForThisChord = 0;

			if (isset($this->scoresConfig['chords'][$chord]))
			{
				$scoreForThisChord = $this->scoresConfig['chords'][$chord];
			}
			else
			{
				foreach ($this->scoresConfig['patterns'] as $pattern=>$score)
				{
					if (preg_match("/$pattern/", $chord))
					{
						$scoreForThisChord = $score;
					}
				}

				if (0 == $scoreForThisChord)
				{
					throw new SongDataException("Unknown chord: $chord");
				}
			}

			$this->scoreMap[$chord] = $scoreForThisChord;
			$this->score += $scoreForThisChord;
		}
	}

	public function setAsBook($asBook)
	{
		$this->asBook = $asBook;
	}

	public function getAsBook()
	{
		return $this->asBook;
	}

	/**
	 * It is necessary to split setter and getter because setter requires NeoApp param, which is not
	 * available in the template.
	 * 
	 * @param NeoApp $app The Silex-Neo app object.
	 */
	public function setCapoForPrint(NeoApp $app)
	{
		$this->capoForPrint = ($this->capo)
				? $app->trans('with capo %n%', array('%n%' => $this->capo))
				: $app->trans('no capo');
	}

	/**
	 * This method should not be invoked from template, because it requires the NeoApp param.
	 * Template should invoke the public attribute capoForPrint, previously set in the controller.
	 * 
	 * @param NeoApp $app The Silex-Neo app object.
	 */
	public function getCapoForPrint(NeoApp $app)
	{
		if (empty($this->capoForPrint))
		{
			$this->setCapoForPrint($app);
		}

		return $this->capoForPrint;
	}

	/**
	 * In most of songs, the key is equal to the first chord. If not, no
	 * alternative chords are calculated. Yes, that's simple.
	 *
	 * @param  \NeoTransposer\Model\NotesCalculator $nc An instance of NotesCalculator
	 * @return string The key, expressed as major chord in american notation.
	 */
	public function getKey(NotesCalculator $nc)
	{
		$first_chord = $nc->readChord($this->chords[0]);

		/*
		 * Flatten the chord, it is, remove all attributes different from minor.
		 * This is needed because some songs, like Sola a Solo, start with a
		 * 4-note chord (Dm5), or Song of Moses (C7).
		 */
		$first_chord['attributes'] = (false !== strpos($first_chord['attributes'], 'm'))
			? 'm' : '';

		//The key is always expressed in major form, so we resolve the minor
		//relatives, it is, the key will be its third minor.
		if ($first_chord['attributes'] == 'm')
		{
			$position = array_search($first_chord['fundamental'], $nc->accoustic_scale);
			$first_chord['fundamental'] = $nc->arrayIndex($nc->accoustic_scale, $position + 3);
			$first_chord['attributes'] = null; 
		}

		return $first_chord['fundamental'] . $first_chord['attributes'];
	}

	public function setAlternativeChords(NotesCalculator $nc)
	{
		if (!$this->asBook)
		{
			/**
			 * Array keys = musical keys (tonality) in which the replace will be done
			 * Array values = array of chords original => replacement
			 * @var array
			 */
			$alternativeChords = array(
				'G' => array(
					'B' => 'B7'
				),
				'E' => array(
					'B' => 'B7'
				),
			);

			$key = $this->getKey($nc);

			foreach ($this->chords as &$chord)
			{
				if (isset($alternativeChords[$key][$chord]))
				{
					$chord = $alternativeChords[$key][$chord];
				}
			}
			$this->setScore();
		}
	}

	public function getCapo()
	{
		return $this->capo;
	}
}
