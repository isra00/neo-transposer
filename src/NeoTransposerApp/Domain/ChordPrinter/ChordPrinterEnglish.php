<?php

namespace NeoTransposerApp\Domain\ChordPrinter;

/**
 * Notation for chords as in the British songbook.
 */
final class ChordPrinterEnglish extends ChordPrinter
{
	protected $cssClass = 'chord chord-british';

	/**
	 * Return a chord with English notation.
	 *
	 * @param  string $fundamental Chord's root note.
	 * @param  string $attributes  Chord's type or attributes.
	 * @return string              The final notation (HTML).
	 */
	public function printChordInNotation($fundamental, $attributes)
	{
		if ($fundamental == 'A#')
		{
			$fundamental = 'B<em>b</em>';
		}

		return $fundamental . str_replace(
			array('4', '6', '7'),
			array('<sup>4</sup>', '<sup>6</sup>', '<sup>7</sup>'),
			$attributes
		);
	}
}
