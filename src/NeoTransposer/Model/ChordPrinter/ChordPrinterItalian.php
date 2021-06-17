<?php

namespace NeoTransposer\Model\ChordPrinter;

/**
 * Notation for chords as in the 2020+ Italian songbook.
 */
class ChordPrinterItalian extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	/**
	 * Return a chord with Italian notation.
	 * 
	 * @param  string $fundamental Chord's root note.
	 * @param  string $attributes  Chord's type or attributes.
	 * @return string              The final notation (HTML).
	 */
	public function printChordInNotation($fundamental, $attributes)
	{
		$fundamental = \NeoTransposer\Model\NotesNotation::getNotation($fundamental, 'latin');


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
		if (preg_match('/^([0-9]|dim)/', $attributes))
		{
			$attributes = " $attributes";
		}

		return $fundamental . $attributes;
	}
}
