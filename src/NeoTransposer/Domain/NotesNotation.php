<?php

namespace NeoTransposer\Domain;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Convert different nomenclatures for notes (american and latin so far).
 */
class NotesNotation
{
    /**
     * Correspondence between American and Latin notation. This software always uses
     * American notation (with no flats, only sharps) internally. Beware that,
     * according to the Neocatechumenal songbook, A# is always Bb.
     *
     * @var array
     */
    protected const LATIN_NOTES = [
        'A'  => 'La',
        'A#' => 'Sib',
        'B'  => 'Si',
        'C'  => 'Do',
        'C#' => 'Do#',
        'D'  => 'Re',
        'D#' => 'Re#',
        'E'  => 'Mi',
        'F'  => 'Fa',
        'F#' => 'Fa#',
        'G'  => 'Sol',
        'G#' => 'Sol#'
    ];

    protected const REGEXP_NOTE = '/([ABCDEFG]#?b?)(\d)?/';

    /**
     * Returns a given note in the given notation (american or latin).
     *
     * @param  string $note     Note, in american notation (no flats, only sharps).
     * @param  string $notation 'american' or 'latin'
     * @return string           The note
     */
    public function getNotation(string $note, string $notation): string
    {
        preg_match(self::REGEXP_NOTE, $note, $match);

        $note   = $match[1];
        $octave = $match[2] ?? null;

        $noteInNotation = ('latin' == $notation) ? self::LATIN_NOTES[$note] : $note;
        return $noteInNotation . $octave;
    }

    public function getNotationArray(array $notes, string $notation): array
    {
        $thisObject = $this;
        return array_map(fn($note) => $thisObject->getNotation($note, $notation), $notes);
    }

    /**
     * Returns a user-friendly string with the voice range:
     *
     * @param TranslatorInterface $trans       The Silex Translator object.
     * @param string              $notation    Notation for notes (american|latin)
     * @param string              $lowestNote  Lowest note of the voice range.
     * @param string              $highestNote Highest note of the voice range.
     *
     * @return string             Something like "lowestNote - highestNote +x octaves"
     */
    public function getVoiceRangeAsString(TranslatorInterface $trans, string $notation = 'american', string $lowestNote, string $highestNote): string
    {
        preg_match(self::REGEXP_NOTE, $lowestNote, $match);
        $lowestNote = $match[1];

        preg_match(self::REGEXP_NOTE, $highestNote, $match);
        $highestNote = $match[1];

        if ('latin' == $notation) {
            $lowestNote = $this->getNotation($lowestNote, 'latin');
            $highestNote = $this->getNotation($highestNote, 'latin');
        }

        $octave = (int) $match[2];
        $octave -= 1;

        return "$lowestNote &rarr; $highestNote +$octave " . $trans->trans('oct');
    }
}
