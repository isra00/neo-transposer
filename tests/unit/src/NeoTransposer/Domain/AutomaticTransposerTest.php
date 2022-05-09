<?php

namespace NeoTransposerApp\Tests\Domain;

use NeoTransposerApp\Domain\AutomaticTransposer;
use NeoTransposerApp\Domain\NotesCalculator;
use NeoTransposerApp\Domain\PeopleCompatibleCalculation;
use NeoTransposerApp\Domain\Transposition;
use NeoTransposerApp\Domain\TranspositionFactory;
use NeoTransposerApp\Domain\ValueObject\Chord;
use NeoTransposerApp\Domain\ValueObject\NotesRange;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;

/**
 * @todo Add some corner cases to transposition algorithms
 */
class AutomaticTransposerTest extends TestCase
{
    protected $sut;

    protected $chordsScoreConfig;

    protected $app;

    public function setUp() : void
    {
        //includePath must be defined in phpunit.xml
        $this->chordsScoreConfig = include __DIR__ . '/../../../../../etc/config.scores.php';
    }

    protected function buildAutomaticTransposer(
        NotesRange $singerRange,
        NotesRange $songRange,
        array $originalChords,
        $firstChordIsKey,
        NotesRange $songPeopleRange = null
    ): AutomaticTransposer {
        return new AutomaticTransposer(
            new NotesCalculator(),
            new TranspositionFactory($this->buildApp()),
            new NotesRange('B1', 'B2'),
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }

    protected function buildAutomaticTransposerWithValues()
    {
        return $this->buildAutomaticTransposer(
            new NotesRange('G1', 'G3'),
            new NotesRange('B1', 'B2'),
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('F'), Chord::fromString('C')],
            false,
            new NotesRange('B1', 'B2')
        );
    }

    protected function buildApp()
    {
        if (empty($this->app)) {
            $this->app = new \Silex\Application([
                'neoconfig' => [
                    'chord_scores' => $this->chordsScoreConfig,
                    'people_range' => ['B1', 'B2'],
                ],
                'translator' => $this->createStub(Translator::class)
            ]);

            $this->app[Transposition::class] = $this->app->factory(
                function ($app) {
                    return $this->buildTransposition($app);
                }
            );
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
    ): Transposition {
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

    public function testCalculateCenteredTransposition()
    {
        $expected = $this->buildTransposition(
            [Chord::fromString('Bm'), Chord::fromString('Em'), new Chord('G'), new Chord('D')],
            0,
            false,
            2,
            new NotesRange('C#2', 'C#3'),
            0,
            new NotesRange('C#2', 'C#3')
        );

        $this->assertEquals(
            $expected,
            $this->buildAutomaticTransposerWithValues()->calculateCenteredTransposition()
        );
    }

    public function testFindCenteredTranspositionAsBook()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('F1', 'F3'),
            new NotesRange('B1', 'B2'),
            [Chord::fromString('Bm'), Chord::fromString('Em'), new Chord('G'), new Chord('D')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->buildTransposition(
            [Chord::fromString('Bm'), Chord::fromString('Em'), new Chord('G'), new Chord('D')],
            0,
            true,
            0,
            new NotesRange('B1', 'B2'),
            0,
            new NotesRange('B1', 'B2')
        );

        $this->assertEquals($expected, $sut->calculateCenteredTransposition());
    }

    public function testCalculateEquivalentsWithCapo()
    {
        $testTransposition = $this->buildTransposition(
            [Chord::fromString('Bm'), Chord::fromString('Em'), new Chord('G'), new Chord('D')],
            0,
            false,
            0,
            null,
            0,
            null
        );

        $equivalents = $this->buildAutomaticTransposerWithValues()->calculateEquivalentsWithCapo($testTransposition);

        $expected = [
            1=> $this->buildTransposition( ['A#m', 'D#m', 'F#', 'C#'], 1, false),
            $this->buildTransposition( ['Am', 'Dm', 'F', 'C'], 2, true),
            $this->buildTransposition( ['G#m', 'C#m', 'E', 'B'], 3, false),
            $this->buildTransposition( ['Gm', 'Cm', 'D#', 'A#'], 4, false),
            $this->buildTransposition( ['F#m', 'Bm', 'D', 'A'], 5, false)
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
            $this->buildAutomaticTransposerWithValues()->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
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
            $this->buildAutomaticTransposerWithValues()->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
        );
    }

    public function testGetEasierNotEquivalent()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'D3'),
            new NotesRange('C#2', 'E3'),
            [Chord::fromString('D'), Chord::fromString('F#'), Chord::fromString('Bm'), Chord::fromString('A'), Chord::fromString('G')],
            false,
            new NotesRange('C#2', 'E3')
        );

        $expected = $this->buildTransposition(
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
            $sut->getEasierNotEquivalent()
        );
    }

    public function testForceHighestVoice()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [Chord::fromString('Am'), Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->buildTransposition(
            [Chord::fromString('Em'), Chord::fromString('D')],
            0,
            false,
            7,
            new NotesRange('B2', 'E3')
        );

        $this->assertEquals(
            $expected,
            $sut->calculateCenteredTransposition(AutomaticTransposer::FORCE_HIGHEST)
        );
    }

    public function testForceLowestVoice()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('E2', 'A2'),
            [Chord::fromString('Am'), Chord::fromString('G')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = $this->buildTransposition(
            [Chord::fromString('Dm'), Chord::fromString('C')],
            0,
            false,
            -7,
            new NotesRange('A1', 'D2')
        );

        $this->assertEquals(
            $expected,
            $sut->calculateCenteredTransposition(AutomaticTransposer::FORCE_LOWEST)
        );
    }

    public function testPeopleCompatibleNoData()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], true
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA,
            null
        );

        $this->assertEquals(
            $expected,
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAlreadyCompatible()
    {
        $sut = $this->buildAutomaticTransposer(
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
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderThanSinger()
    {
        $sut = $this->buildAutomaticTransposer(
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
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderNotAdjusted()
    {
        $sut = $this->buildAutomaticTransposer(
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
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleWiderAdjusted()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'D3'),
            [Chord::fromString('Am'), Chord::fromString('E')],
            true,
            new NotesRange('A1', 'D3')
        );

        $ppc = $this->buildTransposition(
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
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedButStillTooHigh()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('A1', 'D3'),
            [Chord::fromString('Am'), Chord::fromString('Dm')],
            true,
            new NotesRange('G#2', 'D3')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE,
            $this->buildTransposition(
                [Chord::fromString('Em'), Chord::fromString('Am')],
                5,
                false,
                0,
                new NotesRange('A1', 'D3'),
                -1,
                new NotesRange('G#2', 'D3')
            )
        );

        $this->assertEquals(
            $expected,
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellHigh()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('B1', 'B2'),
            [Chord::fromString('D'), Chord::fromString('Em')],
            false,
            new NotesRange('B1', 'B2')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ADJUSTED_WELL,
            $this->buildTransposition(
                ['D', 'Em'],
                0,
                true,
                0,
                new NotesRange('B1', 'B2'),
                -2,
                new NotesRange('B1', 'B2')
            )
        );

        $this->assertEquals(
            $expected,
            $sut->calculatePeopleCompatible()
        );
    }

    public function testPeopleCompatibleAdjustedWellLow()
    {
        $sut = $this->buildAutomaticTransposer(
            new NotesRange('A1', 'E3'),
            new NotesRange('B1', 'E3'),
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('E')],
            true,
            new NotesRange('B1', 'F2')
        );

        $expected = new PeopleCompatibleCalculation(
            PeopleCompatibleCalculation::ADJUSTED_WELL,
            $this->buildTransposition(
                ['Am', 'Dm', 'E'],
                0,
                true,
                0,
                new NotesRange('B1', 'E3'),
                1,
                new NotesRange('B1', 'F2')
            )
        );

        $this->assertEquals(
            $expected,
            $sut->calculatePeopleCompatible()
        );
    }
}
