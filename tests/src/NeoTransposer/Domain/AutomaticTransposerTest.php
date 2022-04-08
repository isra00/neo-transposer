<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\AutomaticTransposer;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * @todo Add some corner cases to transposition algorithms
 */
class AutomaticTransposerTest extends \PHPUnit\Framework\TestCase
{
    protected $sut;

    protected $chordsScoreConfig;

    protected $app;

    public function setUp() : void
    {
        //includePath must be defined in phpunit.xml
        $this->chordsScoreConfig = include __DIR__ . '/../../../../config.scores.php';

        $this->sut = new AutomaticTransposer(
            new TranspositionFactory($this->getSilexApp()), new NotesRange('B1', 'B2')
        );

        $this->sut->setTransposerData(
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

            $this->app[Transposition::class] = $this->app->factory(
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
        $expected = new Transposition(
            $this->getSilexApp(),
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
            $this->sut->calculateCenteredTransposition()
        );
    }

    public function testFindCenteredTranspositionAsBook()
    {
        $this->sut->setTransposerData(
            new NotesRange('F1', 'F3'),
            new NotesRange('B1', 'B2'),
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = new Transposition(
            $this->getSilexApp(),
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            0,
            true,
            0,
            new NotesRange('B1', 'B2'),
            0,
            new NotesRange('B1', 'B2')
        );

        $this->assertEquals($expected, $this->sut->calculateCenteredTransposition());
    }

    public function testCalculateEquivalentsWithCapo()
    {
        $testTransposition = new Transposition(
            $this->getSilexApp(),
            [new Chord('B', 'm'), new Chord('E', 'm'), new Chord('G'), new Chord('D')],
            0,
            false,
            0,
            null,
            0,
            null
        );

        $equivalents = $this->sut->calculateEquivalentsWithCapo($testTransposition);

        $expected = [
            1=> new Transposition($this->getSilexApp(), ['A#m', 'D#m', 'F#', 'C#'], 1, false),
            new Transposition($this->getSilexApp(), ['Am', 'Dm', 'F', 'C'], 2, true),
            new Transposition($this->getSilexApp(), ['G#m', 'C#m', 'E', 'B'], 3, false),
            new Transposition($this->getSilexApp(), ['Gm', 'Cm', 'D#', 'A#'], 4, false),
            new Transposition($this->getSilexApp(), ['F#m', 'Bm', 'D', 'A'], 5, false)
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
            $this->sut->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
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
            $this->sut->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
        );
    }

    public function testGetEasierNotEquivalent()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'D3'),
            new NotesRange('C#2', 'E3'),
            [Chord::fromString('D'), Chord::fromString('F#'), Chord::fromString('Bm'), Chord::fromString('A'), Chord::fromString('G')],
            false,
            new NotesRange('C#2', 'E3')
        );

        $expected = new Transposition(
            $this->getSilexApp(),
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
            $this->sut->getEasierNotEquivalent()
        );
    }

    public function testForceHighestVoice()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [Chord::fromString('Am'), Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = new Transposition($this->getSilexApp(), 
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
            $this->sut->calculateCenteredTransposition(AutomaticTransposer::FORCE_HIGHEST)
        );
    }

    public function testForceLowestVoice()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [
                Chord::fromString('Am'),
                Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = new Transposition(
            $this->getSilexApp(),
            [Chord::fromString('Dm'), Chord::fromString('C')],
            0,
            false,
            -7,
            new NotesRange('A1', 'D2')
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculateCenteredTransposition(AutomaticTransposer::FORCE_LOWEST)
        );
    }

    public function testPeopleCompatibleNoData()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], true
        );

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA,
            null
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAlreadyCompatible()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A2', 'F3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A2', 'D3')
        );

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::ALREADY_COMPATIBLE,
            null
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderThanSinger()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'F3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A2', 'D3')
        );

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::WIDER_THAN_SINGER,
            null
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderNotAdjusted()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('D2', 'F#3'),
            [Chord::fromString('Em'), Chord::fromString('D')],
            true,
            new NotesRange('D2', 'E3')
        );

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER,
            null
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderAdjusted()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'D3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A1', 'D3')
        );

        $ppc = new Transposition($this->getSilexApp(), 
            [Chord::fromString('Am'), Chord::fromString('E')],
            2,
            true,
            2,
            new NotesRange('B1', 'E3'),
            1
        );
        $ppc->peopleRange = new NotesRange('B1', 'E3');

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::ADJUSTED_WIDER,
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedButStillTooHigh()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'D3'),
            [Chord::fromString('Am'), Chord::fromString('Dm')],
            true, 
            new NotesRange('G#2', 'D3')
        );

        $ppc = new Transposition(
            $this->getSilexApp(),
            [Chord::fromString('Em'), Chord::fromString('Am')],
            5,
            false,
            0,
            new NotesRange('A1', 'D3'),
            -1
        );
        $ppc->peopleRange = new NotesRange('G#2', 'D3');

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE,
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellHigh()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('B1', 'B2'),
            [Chord::fromString('D'), Chord::fromString('Em')],
            false, 
            new NotesRange('B1', 'B2')
        );

        $ppc = new Transposition(
            $this->getSilexApp(), ['D', 'Em'], 0, true, 0, new NotesRange('B1', 'B2'), -2, new NotesRange('B1', 'B2')
        );

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::ADJUSTED_WELL,
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellLow()
    {
        $this->sut->setTransposerData(
            new NotesRange('A1', 'E3'),
            new NotesRange('B1', 'E3'),
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('E')],
            true, 
            new NotesRange('B1', 'F2')
        );

        $ppc = new Transposition($this->app, ['Am', 'Dm', 'E'], 0, true, 0, new NotesRange('B1', 'E3'), 1, new NotesRange('B1', 'F2'));

        $expected = new \NeoTransposer\Domain\PeopleCompatibleCalculation(
            \NeoTransposer\Domain\PeopleCompatibleCalculation::ADJUSTED_WELL,
            $ppc
        );

        $this->assertEquals(
            $expected,
            $this->sut->calculatePeopleCompatible()
        );
    }
}