<?php

namespace NeoTransposer\Domain\ChordPrinter;

use NeoTransposer\Domain\NotesNotation;

/**
 * Notation for chords as in the 2020+ Italian songbook.
 */
final class ChordPrinterItalian extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	/**
	 * Return a chord with Italian notation.
	 *
	 * @param  string $fundamental Chord's root note.
	 * @param  string $attributes  Chord's type or attributes.
	 * @return string              The final notation (HTML).
	 */
	public function printChordInNotation($fundamental, $attributes): string
    {
        $notesNotation = new NotesNotation();
		$fundamental = $notesNotation->getNotation($fundamental, 'latin');

		if ($fundamental == 'Sib')
		{
			$fundamental = 'Si <em>b</em>';
		}

		if ($fundamental == 'Re#')
		{
			$fundamental = 'Mi <em>b</em>';
		}

		$fundamental = str_replace('#', ' <em>d</em>', $fundamental);

		if (false === strpos($attributes, 'dim'))
		{
			$attributes = str_replace(
				['m', 'M'],
				['-', 'aum'],
				$attributes
			);
		}

		//Add initial space if attributes are numbers or dim
		if (preg_match('/([0-9]|dim)/', $attributes, $match))
		{
			//$attributes = " " . $match;
			$attributes = str_replace($match[1], " " . $match[1], $attributes);
		}

		return $fundamental . $attributes;
	}
}
