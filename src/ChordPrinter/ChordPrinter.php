<?php

namespace NeoTransposer\ChordPrinter;

abstract class ChordPrinter
{
	protected $cssClass = 'chord';

	public function printTransposition($transposition)
	{
		$transposition->chords = $this->printChordset($transposition->chords);
		return $transposition;
	}

	public function printChordset($chordset)
	{
		foreach ($chordset as &$chord)
		{
			$chord = $this->printChordHtml($chord);
		}

		return $chordset;
	}

	public function printChord($chordName)
	{
		//If chord notation is not valid, it will throw an exception
		$nc = new \NeoTransposer\NotesCalculator;
		$parts = $nc->readChord($chordName);
		return $this->printChordInNotation($parts['fundamental'], $parts['attributes']);
	}

	public function printChordHtml($chordName)
	{
		return '<span class="' . $this->cssClass . '">' . $this->printChord($chordName) . '</span>';
	}

	abstract public function printChordInNotation($fundamental, $attributes);
}