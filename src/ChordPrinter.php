<?php

require_once 'AutomaticTransposer.php';

abstract class ChordPrinter
{
	protected $cssClass = 'chord';

	public function printTransposition($transposition, $html = false)
	{
		$transposition->chords = $this->printChordset($transposition->chords, $html);
		return $transposition;
	}

	public function printChordset($chordset, $html = false)
	{
		foreach ($chordset as &$chord)
		{
			$chord = ($html) 
				? $this->printChordHtml($chord) 
				: $this->printChord($chord);
		}

		return $chordset;
	}

	public function printChord($chordName)
	{
		//If chord notation is not valid, it will throw an exception
		$tr = new AutomaticTransposer;
		$parts = $tr->readChord($chordName);
		return $this->printChordInNotation($parts['fundamental'], $parts['attributes']);
	}

	public function printChordHtml($chordName)
	{
		return '<span class="' . $this->cssClass . '">' . $this->printChord($chordName) . '</span>';
	}

	abstract public function printChordInNotation($fundamental, $attributes);
}