<?php

namespace NeoTransposer\Domain\ChordPrinter;

use NeoTransposer\Domain\NotesNotation;

/**
 * Notation for chords as in the French songbook. The book itself has inconsistent representation of the numbers
 * (La7 and La 7), so we've chosen La 7 as it's more frequent and readable.
 */
final class ChordPrinterFrench extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	/**
	 * Return a chord with French notation.
	 *
	 * @param  string $fundamental Chord's root note.
	 * @param  string $attributes  Chord's type or attributes.
	 * @return string              The final notation (HTML).
	 */
	public function printChordInNotation($fundamental, $attributes): string
	{
		$notesNotation = new NotesNotation();
		$fundamental = str_replace(
			'b',
			'<em>b</em>',
			$notesNotation->getNotation($fundamental, 'latin')
		);

		if (str_contains($attributes, 'dim'))
		{
			return $fundamental . ' <em>dim</em>';
		}

		//Minor first, so that the "m" inserted here is not mistaken for the one in "maj"
		$attributes = preg_replace('/m(\d*)/', '<em>m</em> $1', $attributes);
		$attributes = preg_replace('/(\d*)M/', '<em>maj$1</em>', $attributes);

		return rtrim($fundamental . ' ' . $attributes);
	}
}
