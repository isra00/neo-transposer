<?php

namespace NeoTransposer\Domain;

use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\Exception\SongDataException;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Silex\Application;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Represents a transposition of a song, with transported chords, capo, etc.
 */
class Transposition
{
    /**
     * Transposed chords
     *
     * @var array
     */
    public $chords = [];

    /**
     * Transposed chords, after being processed by a ChordPrinter
     *
     * @var array
     */
    public $chordsForPrint = [];

    /**
     * Difficulty score
     *
     * @var int
     */
    public $score = 0;

    /**
     * Capo number for the transposition
     *
     * @var int
     */
    protected $capo = 0;

    /**
     * Capo number for the transposition, ready to be shown in the UI.
     *
     * @var string
     */
    protected $capoForPrint;

    /**
     * Whether the transposition is the same as the original one.
     *
     * @var bool
     */
    protected $asBook = false;

    /**
     * Offset used for transport from the original.
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Song's lowest and highest note after transposing.
     *
     * @var NotesRange
     */
    public $range;

    /**
     * @var NotesRange
     */
    public $peopleRange;

    /**
     * Deviation from the centered transposition (in semitones), used by NotEquivalent and PeopleCompatible.
     *
     * @var integer
     */
    public $deviationFromCentered = 0;

    /**
     * Used only for debug
     *
     * @var array
     */
    public $scoreMap = [];

    protected $scoresConfig = [];

    protected $translator;

    /**
     * Array keys = musical keys (tonality) in which the replace will be done
     * Array values = array of chords original => replacement
     *
     * @var array
     */
    public const ALTERNATIVE_CHORDS = [
        'G' => [
            'B' => 'B7'
        ],
        'E' => [
            'B' => 'B7'
        ],
    ];

    /**
     * @param array               $scoresConfig
     * @param TranslatorInterface $translator
     * @param array               $chords
     * @param int|null            $capo
     * @param bool|null           $asBook
     * @param int|null            $offset
     * @param NotesRange|null     $range
     * @param int|null            $deviationFromCentered
     * @param NotesRange|null     $peopleRange
     *
     * @throws SongDataException
     */
    public function __construct(
        array $scoresConfig,
        TranslatorInterface $translator,
        array $chords = [],
        ?int $capo = 0,
        ?bool $asBook = false,
        ?int $offset = 0,
        ?NotesRange $range = null,
        ?int $deviationFromCentered = 0,
        ?NotesRange $peopleRange = null
    ) {
        $this->translator = $translator;
        $this->scoresConfig = $scoresConfig;
        $this->chords = $chords;
        $this->capo = $capo;
        $this->asBook = $asBook;
        $this->offset = $offset;
        $this->range = $range;
        $this->peopleRange = $peopleRange;
        $this->deviationFromCentered = $deviationFromCentered;

        $this->setScore();

        return $this; //For fluent constructions
    }

    /**
     * Calculates the ease of the transposition, based on each chord's ease.
     * @throws SongDataException
     */
    public function setScore(): void
    {
        $this->score = 0;

        foreach ($this->chords as $chord) {
            $scoreForThisChord = 0;

            if (isset($this->scoresConfig['chords'][strval($chord)])) {
                $scoreForThisChord = $this->scoresConfig['chords'][strval($chord)];
            } else {
                foreach ($this->scoresConfig['patterns'] as $pattern => $score) {
                    if (preg_match("/$pattern/", strval($chord))) {
                        $scoreForThisChord = $score;
                    }
                }

                if (0 == $scoreForThisChord) {
                    throw new SongDataException("Unknown chord: " . strval($chord));
                }
            }

            $this->scoreMap[strval($chord)] = $scoreForThisChord;
            $this->score += $scoreForThisChord;
        }
    }

    public function setAsBook(bool $asBook): void
    {
        $this->asBook = $asBook;
    }

    public function getAsBook(): bool
    {
        return $this->asBook;
    }

    /**
     * Returns a friendly-formatted string with the capo of the transposition.
     */
    public function getCapoForPrint(): string
    {
        if (empty($this->capoForPrint)) {
            $this->capoForPrint = ($this->capo)
                ? $this->translator->trans('with capo %n%', array('%n%' => $this->capo))
                : $this->translator->trans('no capo');
        }

        return $this->capoForPrint;
    }

    public function setChordsForPrint(ChordPrinter $chordPrinter): void
    {
        $this->chordsForPrint = $chordPrinter->printChordset($this->chords);
    }

    /**
     * In most of the songs, the key is equal to the first chord. If not, no
     * alternative chords are calculated. Yes, that's simple.
     *
     * @param NotesCalculator $ncalc An instance of NotesCalculator
     *
     * @return string The key, expressed as major chord in american notation.
     * @throws SongDataException
     */
    public function getKey(NotesCalculator $ncalc): string
    {
        $firstChord = Chord::fromString($this->chords[0]);

        /*
		 * Flatten the chord, that is, remove all attributes different from minor.
		 * This is needed because some songs, like Sola a Solo, start with a
		 * 4-note chord (Dm5), or Song of Moses (C7).
		 */
        $firstChord->attributes = (false !== strpos($firstChord->attributes, 'm'))
            ? 'm' : '';

        //The key is always expressed in major form, so we resolve the minor
        //relatives, it is, the key will be its third minor.
        if ($firstChord->attributes == 'm') {
            $position = intval(array_search($firstChord->fundamental, NotesCalculator::ACOUSTIC_SCALE));
            $firstChord->fundamental = $ncalc->arrayIndex(NotesCalculator::ACOUSTIC_SCALE, $position + 3);
            $firstChord->attributes = null;
        }

        return $firstChord->fundamental . $firstChord->attributes;
    }

    public function setAlternativeChords(NotesCalculator $nc): void
    {
        if (!$this->asBook) {
            $key = $nc->getKey($this->chords[0]);

            /** @todo Refactor with array_walk */
            foreach ($this->chords as &$chord) {
                $chord = Chord::fromString(self::ALTERNATIVE_CHORDS[$key][strval($chord)] ?? $chord);
            }
            $this->setScore();
        }
    }

    public function getCapo(): int
    {
        return $this->capo;
    }

    public function calculatePeopleRange(
        NotesRange $originalPeopleRange,
        int $offset,
        NotesCalculator $notesCalculator
    ): void {
        $this->peopleRange = $notesCalculator->transposeRange($originalPeopleRange, $offset);
    }
}
