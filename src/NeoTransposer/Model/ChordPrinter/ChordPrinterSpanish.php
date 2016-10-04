<?php

namespace NeoTransposer\Model\ChordPrinter;

/**
 * Notation for chords as in the Spanish songbook.
 */
class ChordPrinterSpanish extends ChordPrinter
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

		return \NeoTransposer\Model\NotesNotation::getNotation($fundamental, 'latin') . $attributes;
	}
}
