<?php

namespace NeoTransposer\Model\ChordPrinter;

use NeoTransposer\Model\NotesNotation;

/**
 * Notation for chords as in the Spanish songbook.
 */
final class ChordPrinterSpanish extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	/**
	 * Return a chord with Spanish notation.
	 * 
	 * @param  string $fundamental Chord's root note.
	 * @param  string $attributes  Chord's type or attributes.
	 * @return string              The final notation (HTML).
	 */
	public function printChordInNotation($fundamental, $attributes)
	{
		if (false === strpos($attributes, 'dim'))
		{
			$attributes = str_replace(
				array('m', 'M'),
				array('-', 'aum'),
				$attributes
			);
		}

        $notesNotation = new NotesNotation();
		return $notesNotation->getNotation($fundamental, 'latin') . $attributes;
	}
}
