<?php

namespace NeoTransposer\Model\ChordPrinter;

/**
 * Chord printers implement the different chord notations. This follows the
 * Template Method pattern.
 *
 * Internally, chords are notated with the following format:
 * -Note with american notation, like NotesCalculator::$accoustic_scale.
 * -Major is default.
 * -Minor as "m". Example: "Am" = A minor.
 * -Diminished as "dim".
 * -A number represents the n-th interval added. Example: "C7" = C seventh.
 * -Augmented (major) interval as "M" after the number. Example: "C7M"
 */
abstract class ChordPrinter
{
	protected $cssClass = 'chord';

	public function printTransposition(\NeoTransposer\Model\Transposition $transposition)
	{
		$transposition->chordsForPrint = $this->printChordset($transposition->chords);
		return $transposition;
	}

	public function printChordset(array $chordset): array
	{
		foreach ($chordset as &$chord)
		{
			$chord = $this->printChordHtml($chord);
		}

		return $chordset;
	}

	public function printChord($chordName)
	{
		//If internal notation is not valid, it will throw an exception
		$nc = new \NeoTransposer\Model\NotesCalculator();
		$parts = $nc->readChord($chordName);
		return $this->printChordInNotation($parts['fundamental'], $parts['attributes']);
	}

	public function printChordHtml($chordName)
	{
		return '<span class="' . $this->cssClass . '">' . $this->printChord($chordName) . '</span>';
	}

	abstract public function printChordInNotation($fundamental, $attributes);
}
