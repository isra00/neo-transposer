<?php

namespace App\Domain;

use App\Domain\ValueObject\Chord;
use App\Domain\ValueObject\NotesRange;

/**
 * Calculations on notes and chords.
 */
class NotesCalculator
{
    /**
     * All the acoustic notes of the scale, including # but not flats.
     *
     * @var array
     */
    final public const ACOUSTIC_SCALE = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    /**
     * All the accoustic notes (including # but not bemol) of 4 octaves, like in
     * a 4-octave numbered_scale. 4 octaves should be enough for voice range.
     *
     * @var array
     * @refactor Replace by a method numberedScale()
     */
    public $numbered_scale = [];

    public function __construct()
    {
        // Fill the numbered_scale.
        for ($i = 1; $i < 5; $i++) {
            foreach (self::ACOUSTIC_SCALE as $note) {
                $this->numbered_scale[] = $note . $i;
            }
        }
    }

    /**
     * Returns the lowest note in the array.
     *
     * @param array $notes Array of numbered notes.
     * @return string        The lowest note.
     */
    public function lowestNote(array $notes)
    {
        $minNumber = count($this->numbered_scale);
        $minNote = null;

        foreach ($notes as $note) {
            $number = array_search($note, $this->numbered_scale);

            if ($number === false) {
                throw new \InvalidArgumentException("'$note' is not a valid note");
            }

            if ($number < $minNumber) {
                $minNumber = $number;
                $minNote = $note;
            }
        }

        return $minNote;
    }

    /**
     * Reads an element of an array, supporting negative indexes and cyclic index.
     *
     * @param array   $array Any indexed array.
     * @param integer $index Index to read
     * @return mixed            The array element
     */
    public function arrayIndex(array $array, int $index)
    {
        if (abs($index) > count($array) - 1) {
            $index %= count($array);
        }

        return ($index < 0)
            ? $array[count($array) + $index]
            : $array[$index];
    }

    /**
     * Transpose a given note with an offset.
     *
     * @param string  $note   The note to transpose
     * @param integer $offset The offset to transpose.
     * @return string            The transposed note.
     */
    public function transposeNote($note, $offset)
    {
        return $this->arrayIndex($this->numbered_scale, array_search($note, $this->numbered_scale) + $offset);
    }

    /** @todo Refactor: podría ser violación de Tell, Don't Ask => mover a NotesRange */
    public function transposeRange(NotesRange $range, $offset): NotesRange
    {
        return new NotesRange(
            $this->transposeNote($range->lowest, $offset),
            $this->transposeNote($range->highest, $offset)
        );
    }

    /**
     * Calculates the absolute distance (in semitones) between two notes, with octave specified.
     *
     * @param string $note1 Note, specified as [note name][octave number], e.g. E3. If NotesRange, no need of $note2.
     * @param string $note2 Another note, following the same pattern as $note1.
     * @return int            Distance in semitones.
     */
    public function distanceWithOctave(string $note1, string $note2): int
    {
        return (int) array_search($note1, $this->numbered_scale) - (int) array_search($note2, $this->numbered_scale);
    }

    public function rangeWideness(NotesRange $range): int
    {
        return $this->distanceWithOctave($range->highest, $range->lowest);
    }

    /**
     * Transpose a chord adding or subtracting semitones.
     *
     * @param int   $amount Number of semitones to add or substract.
     * @return Chord Final chord.
     */
    public function transposeChord(Chord $chord, $amount): Chord
    {
        $transposedFundamental = $this->arrayIndex(
            self::ACOUSTIC_SCALE,
            (int) array_search($chord->fundamental, self::ACOUSTIC_SCALE) + $amount
        );

        return new Chord($transposedFundamental, $chord->attributes);
    }

    /*
     * Transpose a set of chords adding or subtracting semitones.
     *
     * @param  array 	$chordList 	An array of chords.
     * @param  integer 	$amount 	Number of semitones to add or subtract.
     * @return array 				Final set of chords.
     */
    public function transposeChords($chordList, $amount): array
    {
        return array_map(fn($originalChord) => $this->transposeChord($originalChord, $amount), $chordList);
    }

    /**
     * In most of the songs, the key is equal to the first chord. If not, no
     * alternative chords are calculated. This method calculates the key of a
     * song based on the chord passed (which should be the first one), in the
     * form of a major scale (e.g. first chord is G => G; first chord is Em => G).
     *
     *
     * @return string The key, expressed as major chord in american notation.
     */
    public function getKey(Chord $firstChord): string
    {
        //Search 'm' to support all kinds of minor chords.
        return (str_contains($firstChord->attributes, 'm'))
            ? $this->arrayIndex(
                self::ACOUSTIC_SCALE,
                (int) array_search($firstChord->fundamental, self::ACOUSTIC_SCALE) + 3
            )
            : $firstChord->fundamental;
    }
}
