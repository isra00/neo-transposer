<?php

namespace NeoTransposer\Model;

use \Silex\Translator;

/**
 * Support for different nomenclatures for notes (american and latin so far).
 */
class NotesNotation
{
	/**
	 * Correspondence between American and Latin notation. The softwares uses
	 * always american notation (with no flats, only sharps) internally.
	 * @var array
	 */
	protected static $latinNotes = array(
		'A'		=> 'La',
		'A#'	=> 'Sib',
		'B' 	=> 'Si',
		'C'		=> 'Do',
		'C#'	=> 'Do#',
		'D'		=> 'Re',
		'D#'	=> 'Re#',
		'E'		=> 'Mi',
		'F'		=> 'Fa',
		'F#'	=> 'Fa#',
		'G'		=> 'Sol',
		'G#'	=> 'Sol#'
	);

	/**
	 * Returns a given note in the given notation (american or latin).
	 * @param  string $note     Note, in american notation (no flats, only sharps).
	 * @param  string $notation 'american' or 'latin'
	 * @return string           The note
	 */
	public static function getNotation($note, $notation)
	{
		return ('latin' == $notation) ? self::$latinNotes[$note] : $note;
	}

	/**
	 * Returns a user-friendly string with the voice range:
	 * 
	 * @param  Translator $trans       The Silex Translator object.
	 * @param  string     $notation    Notation for notes (american|latin)
	 * @param  string     $lowestNote  Lowest note of the voice range.
	 * @param  string     $highestNote Highest note of the voice range.
	 * @return string                  Something like "lowest_note - highest note +x octaves"
	 */
	public static function getVoiceRangeAsString(Translator $trans, $notation='american', $lowestNote, $highestNote)
	{
		$regexp = '/([ABCDEFG]#?b?)([0-9])/';
		
		preg_match($regexp, $lowestNote, $match);
		$lowest_note = $match[1];

		preg_match($regexp, $highestNote, $match);
		$highest_note = $match[1];

		if ('latin' == $notation)
		{
			$lowest_note = NotesNotation::getNotation($lowest_note, 'latin');
			$highest_note = NotesNotation::getNotation($highest_note, 'latin');
		}

		$octave = intval($match[2]);
		$octave = $octave - 1;

		return "$lowest_note &rarr; $highest_note +$octave " . $trans->trans('oct');
	}
}