<?php

namespace NeoTransposer;

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
	 * Difficulty score
	 * @var integer
	 */
	public $score = 0;
	
	/**
	 * Capo number for the transposition
	 * @var integer
	 */
	public $capo = 0;

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
	 * Deviation from the perfect transposition (in semitones).
	 * @var integer
	 */
	public $deviation = 0;

	public function __construct($chords=array(), $capo=0, $asBook=false, $offset=0)
	{
		$this->chords = $chords;
		$this->capo = $capo;
		$this->asBook = $asBook;
		$this->offset = $offset;

		$this->setChordsetEase();
	}

	/**
	 * Calculates the ease of the transposition, based on each chord's ease.
	 */
	function setChordsetEase()
	{
		/*
		 * Ordered from easier to harder. This is according to *MY* experience 
		 * as cantor and the cantors i've met (many ;-)
		 * It does not take into account only the ease of the chord, but also
		 * the probability that the cantor knows it (for example, A9 is pretty
		 * easy but few people know it). Therefore, it also depends on how much
		 * each chord is actually used in the songs of the Way.
		 *
		 * It includes only major, minor and 7th chords.
		 *
		 * @todo Incluir TODOS los acordes que se usen realmente en el libro.
		 *
		 * @todo Implementar soporte a acordes que NO están en esta lista (p. ej. Dm9)
		 * pero que pudieran salir... para ello no mirar los acordes sino solo
		 * la nota fundamental. 
		 *
		 * @todo Cambiar sistema para ponderar acordes. Por ejemplo, Am y E tienen la misma puntuación,
		 *       mientras que D# debería tener más puntuación que la que tiene ahora
		 *       (por posiciones consecutivas), para evitarlo a toda costa.
		 */
		$easyChords = array(
			'Em', 'E', 'Am', 'A', 'D', 'Dm', 'C', 'G', 'E7', 'A7', 'G7', 'D7', 
			'B7', 'F', 'C7', 'G#', 'G#m', 'F#', 'F#m', 'Gm', 'Bm', 'A#', 'C#', 
			'C#7', 'F7', 'F#7', 'B', 'Fm', 'A#7', 'C#m', 'A#m', 'Cm', 'G#7', 'D#',
			'D#m', 'D#7',
		);

		$this->score = 0;

		foreach ($this->chords as $chord)
		{
			$score = array_search($chord, $easyChords);

			//Acordes no registrados se les asigna una dificultad media
			/** @todo Mejorar esto: cuando estén todos metidos, los que no estén
			registrados serán MUY raros, por tanto deberían tener una puntuación
			muy alta */
			if (false === $score)
			{
				$score = count($easyChords) / 2;
			}
			
			$this->score += $score;
		}

		/*
		 * If it's like in the book, although it may not be the simplest chords,
		 * it's simpler to work with for the cantor. If the cantor is looking
		 * specifically for easier chords, he/she has the other options.
		 *
		 * @todo En /transpose_song.php?song=60 se ve que quizá dividir / 2 es demasiado...
		 */
		if ($this->asBook)
		{
			$this->score = $this->score / 2;
		}
	}

	public function setAsBook($asBook)
	{
		$this->asBook = $asBook;
		$this->setChordsetEase();
	}

	public function getAsBook()
	{
		return $this->asBook;
	}
}