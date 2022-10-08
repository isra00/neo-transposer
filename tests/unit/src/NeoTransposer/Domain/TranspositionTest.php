<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Silex\Application;
use Symfony\Component\Translation\Translator;

class TranspositionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Fixture of the SUT.
     * @var Transposition
     */
    protected $transposition;

    /**
     * An instance of NotesCalculator, needed by some methods.
     * @var NotesCalculator;
     */
    protected $notesCalculator;

    protected $app;

    public function setUp(): void
    {
        $this->transposition = $this->buildTransposition(
            [Chord::fromString('Em'), Chord::fromString('Am'), Chord::fromString('B')],
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->notesCalculator = new NotesCalculator();
    }

    protected function buildApp(): Application
    {
        if (empty($this->app)) {
            $this->app = new Application([
                'neoconfig'  => ['chord_scores' => include __DIR__ . '/../../../../../etc/config.scores.php'],
                'translator' => $this->createStub(Translator::class)
            ]);
        }

        return $this->app;
    }

    protected function buildTransposition(
        array $chords = [],
        ?int $capo = 0,
        ?bool $asBook = false,
        ?int $offset = 0,
        ?NotesRange $range = null,
        ?int $deviationFromCentered = 0,
        ?NotesRange $peopleRange = null
    ) {

        return (new TranspositionFactory($this->buildApp()))->createTransposition(
            $chords,
            $capo,
            $asBook,
            $offset,
            $range,
            $deviationFromCentered,
            $peopleRange
        );
    }

    /*public function testGetWithAlternativeChords()
    {
        $expected = new Transposition(array('Em', 'Am', 'B7'));
        $this->assertEquals($expected, $this->transp->getWithAlternativeChords());
    }*/

    public function testGetWithAlternativeChords()
    {
        $this->transposition->setAlternativeChords($this->notesCalculator);
        $this->assertEquals(array('Em', 'Am', 'B7'), $this->transposition->chords);

        // If AsBook, alternative chords should not be calculated.
        $chords2 = array('Em', 'Am', 'B');
        $transposition = $this->buildTransposition($chords2, 0, true, null, null, null, null);
        $transposition->setAlternativeChords($this->notesCalculator);
        $this->assertEquals($chords2, $transposition->chords);
    }

    public function testCalculatePeopleRange()
    {
        $this->transposition->calculatePeopleRange(
            new NotesRange('A1', 'A2'),
            2,
            $this->notesCalculator
        );

        $this->assertEquals(new NotesRange('B1', 'B2'), $this->transposition->peopleRange);
    }
}
