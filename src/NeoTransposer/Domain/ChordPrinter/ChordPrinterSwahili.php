<?php

namespace NeoTransposer\Domain\ChordPrinter;

final class ChordPrinterSwahili extends ChordPrinter
{
	/**
	 * Return a chord with Swahili notation.
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

		$fundamental = str_replace('#', '<em>d</em>', $fundamental);

        return $fundamental . str_replace('m', '-', $attributes);
	}
}
