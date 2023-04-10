<?php

namespace NeoTransposer\Domain;

use Exception;
use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\NeoApp;
use Silex\Application;

/**
 * Read a song from DB, calculate its transpositions, sort them according to
 * some business logic and prepare for print.
 *
 * This class is in an upper level than AutomaticTransposer and is intended to
 * be used by controllers such as TransposeSong, AllSongsReport and WizardEmpiric.
 */
class TransposedSong
{
    /**
     * @var  array
     * @todo Rename to transpositionsCentered
     */
    public $transpositions;

    /**
     * @var  Transposition
     * @todo Rename to transpositionNotEquivalent o transpositionEasierNotEquivalent
     */
    public $not_equivalent;

    /**
     * @var PeopleCompatibleCalculation
     */
    protected $pcCalculation;

    public function __construct(
        public Song $song,
        protected Application $app
    )
    {
    }

    /**
     * @throws Exception
     */
    public static function fromDb($idSong, NeoApp $dc): TransposedSong
    {
        $songRepository = $dc[SongRepository::class];
        return new static($songRepository->fetchSongByIdOrSlug($idSong), $dc);
    }

    /**
     * Main method to be used by the clients of this class. It calculates all
     * transpositions.
     *
     * @param int|null $forceVoiceLimit Force user's lowest or highest note (only used in Wizard).
     *                                  AutomaticTransposer::FORCE_LOWEST or AutomaticTransposer::FORCE_HIGHEST.
     *
     * @throws Exception
     */
    public function transpose(NotesRange $userRange, int $forceVoiceLimit = null): void
    {
        $transposerFactory = $this->app[AutomaticTransposerFactory::class];

        $transposer = $transposerFactory->createAutomaticTransposer(
            $userRange,
            $this->song->range,
            $this->song->originalChords,
            $this->song->firstChordIsTone,
            $this->song->peopleRange
        );

        $this->transpositions = $transposer->getTranspositionsCentered(
            AutomaticTransposer::AMOUNT_CENTERED_TRANSPOSITIONS,
            $forceVoiceLimit
        );
        $this->not_equivalent = $transposer->getEasierNotEquivalent();

        if ($this->app['neoconfig']['people_compatible']) {
            $this->pcCalculation = $transposer->calculatePeopleCompatible();

            if ($this->not_equivalent !== null) {
                $this->removeEasierNotEquivalentIfConflictWithPeopleCompatible();
            }
        }

        //If there is notEquivalent, show only one centered.
        if ($this->not_equivalent && $this->app['neoconfig']['hide_second_centered_if_not_equivalent']) {
            unset($this->transpositions[1]);
        }

        $this->prepareForPrint();
    }

    /**
     * Prepare transpositions for print (chords and capo sentence).
     */
    protected function prepareForPrint(): void
    {
        $chordPrinter = $this->app['factory.ChordPrinter']($this->song->bookChordPrinter);

        $this->song->setOriginalChordsForPrint($chordPrinter);

        array_map(
            function ($transposition) use ($chordPrinter) {
                if (!empty($transposition)) {
                    $transposition->setChordsForPrint($chordPrinter);
                }
            },
            array_merge(
                $this->transpositions,
                [$this->not_equivalent, $this->getPeopleCompatible()]
            )
        );
    }

    public function getPeopleCompatible(): ?Transposition
    {
        return $this->pcCalculation->peopleCompatibleTransposition;
    }

    public function getPeopleCompatibleStatus(): ?int
    {
        return $this->pcCalculation->status;
    }

    /**
     * This IS actually used by transpose_song.twig's "peopleCompatibleStatusMsg"
     */
    public function getPeopleCompatibleStatusMsg(): ?string
    {
        return $this->pcCalculation->getStatusMsg();
    }

    /**
     * User in removeEasierNotEquivalentIfConflictWithPeopleCompatible() and in transpose_song.twig
     */
    public function isAlreadyPeopleCompatible(): bool
    {
        return PeopleCompatibleCalculation::ALREADY_COMPATIBLE == $this->pcCalculation->status;
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
        if (($this->isAlreadyPeopleCompatible() && !$this->isCompatibleWithPeople($this->not_equivalent))
            || $this->pcCalculation->peopleCompatibleTransposition
        ) {
            $this->not_equivalent = null;
        }
    }

    /**
     * Check whether the given transposition is within people's range for the current song.
     *
     *
     * @throws Exception
     */
    protected function isCompatibleWithPeople(Transposition $transposition): bool
    {
        if (empty($this->song->peopleRange)) {
            throw new Exception("Can't call isCompatibleWithPeople for this song because this song has no peopleRange");
        }

        $nc          = new NotesCalculator();
        $peopleRange = new NotesRange(
            $this->app['neoconfig']['people_range'][0],
            $this->app['neoconfig']['people_range'][1]
        );

        return $nc->transposeRange(
            $this->song->peopleRange,
            $transposition->offset
        )->isWithinRange($peopleRange, $nc);
    }
}
