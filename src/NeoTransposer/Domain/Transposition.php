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
     * Capo number for the transposition, ready to be shown in the UI.
     *
     * @var string
     */
    protected $capoForPrint;

    /**
     * Used only for debug
     *
     * @var array
     */
    public $scoreMap = [];

    /**
     * Array keys = musical keys (tonality) in which the replace will be done
     * Array values = array of chords original => replacement
     *
     * @var array
     */
    final public const ALTERNATIVE_CHORDS = [
        'G' => [
            'B' => 'B7'
        ],
        'E' => [
            'B' => 'B7'
        ],
    ];

    /**
     *
     * @throws SongDataException
     */
    public function __construct(
        protected array $scoresConfig,
        protected TranslatorInterface $translator,
        public array $chords = [],
        protected ?int $capo = 0,
        protected ?bool $asBook = false,
        public ?int $offset = 0,
        public ?NotesRange $range = null,
        public ?int $deviationFromCentered = 0,
        public ?NotesRange $peopleRange = null
    ) {
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
                ? $this->translator->trans('with capo %n%', ['%n%' => $this->capo])
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
        $firstChord->attributes = (str_contains((string) $firstChord->attributes, 'm'))
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
