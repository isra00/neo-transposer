<?php

namespace NeoTransposer\Model;

use \Symfony\Component\Translation\TranslatorInterface;

/**
 * Support for different nomenclatures for notes (american and latin so far).
 * 
 * @fixme Solve duplicity of regexp in getNotation() and getVoiceRangeAsString()
 */
class NotesNotation
{
	/**
	 * Correspondence between American and Latin notation. The softwares uses
	 * always american notation (with no flats, only sharps) internally. Beware
	 * that, according to the Neocatechumenal songbook, A# is always Bb.
	 * 
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
		$regexp = '/([ABCDEFG]#?b?)([0-9])?/';

		preg_match($regexp, $note, $match);

		$note 	= $match[1];
		$number = $match[2] ?? null;

		$noteInNotation = ('latin' == $notation) ? self::$latinNotes[$note] : $note;
		return $noteInNotation . $number;
	}

	/**
	 * Returns a user-friendly string with the voice range:
	 * 
	 * @param  Translator $trans       The Silex Translator object.
	 * @param  string     $notation    Notation for notes (american|latin)
	 * @param  string     $lowestNote  Lowest note of the voice range.
	 * @param  string     $highestNote Highest note of the voice range.
	 * @return string                  Something like "lowestNote - highestNote +x octaves"
	 */
	public static function getVoiceRangeAsString(TranslatorInterface $trans, $notation='american', $lowestNote, $highestNote)
	{
		$regexp = '/([ABCDEFG]#?b?)([0-9])/';
		
		preg_match($regexp, $lowestNote, $match);
		$lowestNote = $match[1];

		preg_match($regexp, $highestNote, $match);
		$highestNote = $match[1];

		if ('latin' == $notation)
		{
			$lowestNote = NotesNotation::getNotation($lowestNote, 'latin');
			$highestNote = NotesNotation::getNotation($highestNote, 'latin');
		}

		$octave = intval($match[2]);
		$octave = $octave - 1;

		return "$lowestNote &rarr; $highestNote +$octave " . $trans->trans('oct');
	}
}
