<?php

namespace NeoTransposer\Model\ChordPrinter;

/**
 * Notation for chords as in the British songbook.
 */
class ChordPrinterEnglish extends ChordPrinter
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

		$print_attributes = str_replace(
			array('4', '6', '7'),
			array('<sup>4</sup>', '<sup>6</sup>', '<sup>7</sup>'),
			$attributes
		);

		return $fundamental . $print_attributes;
	}
}
