<?php

namespace NeoTransposer\Domain;

use Exception;
use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * Read a song from DB, calculate its transpositions, sort them according to
 * some business logic and prepare for print.
 *
 * This class is in an upper level than Transposer and is intended to
 * be used by controllers such as TransposeSong, AllSongsReport and WizardEmpiric.
 */
final class TransposedSong
{
    /**
     * @var array
     */
    public $transpositionsCentered;

    public ?Transposition $transpositionEasierNotEquivalent = null;

    private PeopleCompatibleCalculation $pcCalculation;

    public function __construct(public Song $song)
    {
    }

    /**
     * @throws Exception
     */
    public static function fromDbById(int $idSong): TransposedSong
    {
        return new self(app(SongRepository::class)->readSongById($idSong));
    }

    /**
     * @throws Exception
     */
    public static function fromSlug(string $slug): TransposedSong
    {
        return new self(app(SongRepository::class)->readSongBySlug($slug));
    }

    /**
     * Main method to be used by the clients of this class. It calculates all
     * transpositions.
     *
     * @param  int|null  $forceVoiceLimit  Force user's lowest or highest note (only used in Wizard).
     *                                     Transposer::FORCE_LOWEST or Transposer::FORCE_HIGHEST.
     *
     * @throws Exception
     */
    public function transpose(NotesRange $userRange, ?int $forceVoiceLimit = null): void
    {
        $transposerFactory = app(TransposerFactory::class);

        $transposer = $transposerFactory->createTransposer(
            $userRange,
            $this->song->range,
            $this->song->originalChords,
            $this->song->firstChordIsTone,
            $this->song->peopleRange
        );

        $this->transpositionsCentered = $transposer->getTranspositionsCentered(
            Transposer::AMOUNT_CENTERED_TRANSPOSITIONS,
            $forceVoiceLimit
        );
        $this->transpositionEasierNotEquivalent = $transposer->getEasierNotEquivalent();

        $this->pcCalculation = $transposer->calculatePeopleCompatible();

        if ($this->transpositionEasierNotEquivalent !== null) {
            $this->removeEasierNotEquivalentIfConflictWithPeopleCompatible();
        }

        // If there is notEquivalent, show only one centered.
        if ($this->transpositionEasierNotEquivalent && config('nt.hide_second_centered_if_not_equivalent')) {
            unset($this->transpositionsCentered[1]);
        }

        $this->prepareForPrint();
    }

    /**
     * Prepare transpositions for print (chords and capo sentence).
     */
    private function prepareForPrint(): void
    {
        $chordPrinter = app('factory.ChordPrinter')($this->song->bookChordPrinter);

        $this->song->setOriginalChordsForPrint($chordPrinter);

        array_map(
            function ($transposition) use ($chordPrinter) {
                if (!empty($transposition)) {
                    $transposition->setChordsForPrint($chordPrinter);
                }
            },
            array_merge(
                $this->transpositionsCentered,
                [$this->transpositionEasierNotEquivalent, $this->pcCalculation->peopleCompatibleTransposition]
            )
        );
    }

    public function getPeopleCompatible(): ?Transposition
    {
        return $this->pcCalculation->peopleCompatibleTransposition;
    }

    public function getPeopleCompatibleStatus(): int
    {
        return $this->pcCalculation->status;
    }

    /**
     * This IS actually used by transpose_song.twig's "peopleCompatibleStatusMsg"
     */
    public function getPeopleCompatibleStatusMsg(): string
    {
        return $this->pcCalculation->getStatusMsg();
    }

    /**
     * User in removeEasierNotEquivalentIfConflictWithPeopleCompatible() and in transpose_song.twig
     */
    public function isAlreadyPeopleCompatible(): bool
    {
        return $this->pcCalculation->status == PeopleCompatibleCalculation::ALREADY_COMPATIBLE;
    }

    /**
     * If Centered is already compatible but notEquivalent is not, then remove
     * notEquivalent, because other saying "this transposition is already
     * compatible" would be partially false.
     *
     * @throws Exception
     */
    public function removeEasierNotEquivalentIfConflictWithPeopleCompatible(): void
    {
        if (($this->isAlreadyPeopleCompatible() && !$this->isCompatibleWithPeople($this->transpositionEasierNotEquivalent))
            || $this->pcCalculation->peopleCompatibleTransposition
        ) {
            $this->transpositionEasierNotEquivalent = null;
        }
    }

    /**
     * Check whether the given transposition is within people's range for the current song.
     *
     * @throws Exception
     */
    private function isCompatibleWithPeople(Transposition $transposition): bool
    {
        if (empty($this->song->peopleRange)) {
            throw new Exception("Can't call isCompatibleWithPeople for this song because this song has no peopleRange");
        }

        $nc = new NotesCalculator();
        $peopleRange = new NotesRange(
            config('nt.people_range')[0],
            config('nt.people_range')[1]
        );

        return $nc->transposeRange(
            $this->song->peopleRange,
            $transposition->offset
        )->isWithinRange($peopleRange, $nc);
    }
}
