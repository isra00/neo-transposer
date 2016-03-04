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
	 * Deviation from the perfect transposition (in semitones).
	 * @var integer
	 */
	public $deviationFromPerfect = 0;

	/*
	 * The chords, ordered from easier to harder. This is according to *MY*
	 * experience as cantor and the cantors i've met. It does not take into
	 * account only the ease of the chord, but also the probability that the 
	 * cantor knows it (for example, A9 is pretty easy but few people know it).
	 * Therefore, it also depends on how much each chord is actually used in the
	 * songs of the Way.
	 *
	 * @todo Añadir acordes que, aunque no están en el libro, podrían salir por
	 *       ser transposiciones de estos.
	 *
	 * @todo Cambiar sistema para ponderar acordes. Por ejemplo, Am y E tienen
	 *       la misma puntuación, mientras que D# debería tener más puntuación
	 *       que la que tiene ahora (por posiciones consecutivas), para evitarlo
	 *       a toda costa.
	 *
	 * @var array
	 */
	protected static $easyChords = array(
		'Em', 'E', 'Am', 'A', 'D', 'Dm', 'C', 'G', 'E7', 'A7', 'G7', 'D7', 'B7',
		'F', 'C7', 'F#', 'Bm', 'F#m', 'G#', 'G#m', 'Gm', 'A#', 'C#', 'C#7', 
		'Dm9', 'F7', 'F7M', 'F#7', 'B', 'Em6', 'Dm5', 'Fm', 'Am6', 'A#7', 'C#m',
		'A#m', 'Cm', 'G#7', 'D#', 'D#m', 'D#7', 'C#dim', 'Gm6'
	);

	public function __construct($chords=array(), $capo=0, $asBook=false, $offset=0, $lowest_note=null, $highest_note=null, $deviationFromPerfect=0)
	{
		$this->chords		= $chords;
		$this->capo			= $capo;
		$this->asBook 		= $asBook;
		$this->offset 		= $offset;
		$this->lowestNote 	= $lowest_note;
		$this->highestNote 	= $highest_note;
		$this->deviationFromPerfect = $deviationFromPerfect;

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
			$score = array_search($chord, self::$easyChords);

			if (false === $score)
			{
				$score = count(self::$easyChords) / 1.8;
			}
			
			$this->score += $score;
		}
	}

	public function setAsBook($asBook)
	{
		$this->asBook = $asBook;
		$this->setScore(); /** @todo ¿Podría no hacer falta ya? */
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