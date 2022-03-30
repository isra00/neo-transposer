<?php

namespace NeoTransposer\Tests\Model;

use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Model\{AutomaticTransposer,
    NotesRange,
    PeopleCompatibleCalculation,
    Transposition};

/**
 * @todo Add some corner cases to transposition algorithms
 */
class AutomaticTransposerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * An instance of the class under test
     *
     * @var AutomaticTransposer
     */
    protected $transposer;

    protected $chordsScoreConfig;

    protected $app;

    public function setUp() : void
    {
        //includePath must be defined in phpunit.xml
        $this->chordsScoreConfig = include __DIR__ . '/../../../../config.scores.php';

        $this->transposer = new AutomaticTransposer($this->getSilexApp());
        $this->transposer->setTransposerData(
            new NotesRange('G1', 'G3'), 
            new NotesRange('B1', 'B2'), 
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('F'), Chord::fromString('C')],
            false, 
            new NotesRange('B1', 'B2')
        );
    }

    protected function getSilexApp()
    {
        if (empty($this->app)) {
            $this->app = new \Silex\Application;
            $this->app['neoconfig'] = [
            'chord_scores' => $this->chordsScoreConfig,
            'people_range' => ['B1', 'B2'],
            ];

            $this->app['new.Transposition'] = $this->app->factory(
                function ($app) {
                    return new Transposition($app);
                }
            );
        }

        return $this->app;
    }

    protected function createEmptyTransposition()
    {
        return new Transposition($this->app);
    }

    public function testCalculateCenteredTransposition()
    {
        $expected = $this->createEmptyTransposition()->setTranspositionData(
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            0,
            false,
            2,
            new NotesRange('C#2', 'C#3'),
            0,
            new NotesRange('C#2', 'C#3')
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculateCenteredTransposition()
        );
    }

    public function testFindCenteredTranspositionAsBook()
    {
        $this->transposer->setTransposerData(
            new NotesRange('F1', 'F3'),
            new NotesRange('B1', 'B2'),
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->createEmptyTransposition();
        $expected->setTranspositionData(
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            0,
            true,
            0,
            new NotesRange('B1', 'B2'),
            0,
            new NotesRange('B1', 'B2')
        );

        $this->assertEquals($expected, $this->transposer->calculateCenteredTransposition());
    }

    public function testCalculateEquivalentsWithCapo()
    {
        $testTransposition = $this->createEmptyTransposition();
        $testTransposition->setTranspositionData(
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            0,
            false,
            0,
            null,
            0,
            null
        );

        $equivalents = $this->transposer->calculateEquivalentsWithCapo($testTransposition);

        $expected = [
            1=> $this->createEmptyTransposition()->setTranspositionData(['A#m', 'D#m', 'F#', 'C#'], 1, false),
            $this->createEmptyTransposition()->setTranspositionData(['Am', 'Dm', 'F', 'C'], 2, true),
            $this->createEmptyTransposition()->setTranspositionData(['G#m', 'C#m', 'E', 'B'], 3, false),
            $this->createEmptyTransposition()->setTranspositionData(['Gm', 'Cm', 'D#', 'A#'], 4, false),
            $this->createEmptyTransposition()->setTranspositionData(['F#m', 'Bm', 'D', 'A'], 5, false)
        ];

        $this->assertEquals($expected, $equivalents);
    }

    public function testSortTranspositionsByEase()
    {
        $transpositionMockA = $this->getMockBuilder(Transposition::class)
            ->disableOriginalConstructor()
            ->setMethods(['trans'])
            ->getMock();

        $transpositionMockB = clone $transpositionMockA;

        $transpositionMockA->score = 10;
        $transpositionMockB->score = 20;

        $this->assertEquals(
            [$transpositionMockA, $transpositionMockB],
            $this->transposer->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
        );
    }

    public function testSortTranspositionsByEaseWhenEqualScorePrioritizeAsBook()
    {
        $transpositionMockA = $this->getMockBuilder(Transposition::class)
            ->disableOriginalConstructor()
            ->setMethods(['trans'])
            ->getMock();

        $transpositionMockB = clone $transpositionMockA;

        $transpositionMockA->score = 10;
        $transpositionMockB->score = 10;

        $transpositionMockA->setAsBook(true);

        $this->assertEquals(
            [$transpositionMockA, $transpositionMockB],
            $this->transposer->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
        );
    }

    public function testGetEasierNotEquivalent()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'D3'),
            new NotesRange('C#2', 'E3'),
            [Chord::fromString('D'), Chord::fromString('F#'), Chord::fromString('Bm'), Chord::fromString('A'), Chord::fromString('G')],
            false,
            new NotesRange('C#2', 'E3')
        );

        $expected = $this->createEmptyTransposition();
        $expected->setTranspositionData(
            [Chord::fromString('C'), Chord::fromString('E'), Chord::fromString('Am'), Chord::fromString('G'), Chord::fromString('F')],
            0,
            false,
            -2,
            new NotesRange('B1', 'D3'),
            1,
            new NotesRange('B1', 'D3')
        );

        $this->assertEquals(
            $expected,
            $this->transposer->getEasierNotEquivalent()
        );
    }

    public function testForceHighestVoice()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [Chord::fromString('Am'), Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->createEmptyTransposition()->setTranspositionData(
            [Chord::fromString('Em'), Chord::fromString('D')],
            0,
            false,
            7,
            new NotesRange('B2', 'E3'),
            0,
            null
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculateCenteredTransposition(AutomaticTransposer::FORCE_HIGHEST)
        );
    }

    public function testForceLowestVoice()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [
                Chord::fromString('Am'),
                Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->createEmptyTransposition();
        $expected->setTranspositionData(
            [Chord::fromString('Dm'), Chord::fromString('C')],
            0,
            false,
            -7,
            new NotesRange('A1', 'D2')
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculateCenteredTransposition(AutomaticTransposer::FORCE_LOWEST)
        );
    }

    public function testPeopleCompatibleNoData()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], true
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA, 
            null
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAlreadyCompatible()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A2', 'F3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A2', 'D3')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ALREADY_COMPATIBLE, 
            null
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderThanSinger()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'F3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A2', 'D3')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::WIDER_THAN_SINGER, 
            null
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderNotAdjusted()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('D2', 'F#3'),
            [Chord::fromString('Em'), Chord::fromString('D')],
            true,
            new NotesRange('D2', 'E3')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER, 
            null
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderAdjusted()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'D3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A1', 'D3')
        );

        $ppc = $this->createEmptyTransposition()->setTranspositionData(
            [Chord::fromString('Am'), Chord::fromString('E')],
            2,
            true,
            2,
            new NotesRange('B1', 'E3'),
            1
        );
        $ppc->peopleRange = new NotesRange('B1', 'E3');

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ADJUSTED_WIDER, 
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedButStillTooHigh()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'), 
            new NotesRange('A1', 'D3'), 
            [Chord::fromString('Am'), Chord::fromString('Dm')],
            true, 
            new NotesRange('G#2', 'D3')
        );

        $ppc = new Transposition($this->app);
        $ppc->setTranspositionData(
            [Chord::fromString('Em'), Chord::fromString('Am')],
            5,
            false,
            0,
            new NotesRange('A1', 'D3'),
            -1
        );
        $ppc->peopleRange = new NotesRange('G#2', 'D3');

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE, 
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellHigh()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'), 
            new NotesRange('B1', 'B2'), 
            [Chord::fromString('D'), Chord::fromString('Em')],
            false, 
            new NotesRange('B1', 'B2')
        );

        $ppc = new Transposition($this->app);
        $ppc->setTranspositionData(['D', 'Em'], 0, true, 0, new NotesRange('B1', 'B2'), -2);
        $ppc->peopleRange = new NotesRange('B1', 'B2');

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ADJUSTED_WELL, 
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellLow()
    {
        $this->transposer->setTransposerData(
            new NotesRange('A1', 'E3'), 
            new NotesRange('B1', 'E3'), 
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('E')],
            true, 
            new NotesRange('B1', 'F2')
        );

        $ppc = new Transposition($this->app);
        $ppc->setTranspositionData(['Am', 'Dm', 'E'], 0, true, 0, new NotesRange('B1', 'E3'), 1);
        $ppc->peopleRange = new NotesRange('B1', 'F2');

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ADJUSTED_WELL, 
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->transposer->calculatePeopleCompatible()
        );
    }
}