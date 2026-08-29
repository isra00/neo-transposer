<?php

namespace NeoTransposer\Tests\Domain;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\PeopleCompatibleCalculation;
use NeoTransposer\Domain\Transposer;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * @todo Add some corner cases to transposition algorithms
 */
class TransposerTest extends TestCase
{
    protected $sut;

    protected $transpositionFactory;

    protected function buildTransposer(
        NotesRange $singerRange,
        NotesRange $songRange,
        array $originalChords,
        $firstChordIsKey,
        ?NotesRange $songPeopleRange = null
    ): Transposer {
        return new Transposer(
            new NotesCalculator(),
            $this->getTranspositionFactory(),
            new NotesRange('B1', 'B2'),
            $singerRange,
            $songRange,
            $originalChords,
            $firstChordIsKey,
            $songPeopleRange
        );
    }

    protected function buildTransposerWithValues()
    {
        return $this->buildTransposer(
            new NotesRange('G1', 'G3'),
            new NotesRange('B1', 'B2'),
            [Chord::fromString('Am'), Chord::fromString('Dm'), Chord::fromString('F'), Chord::fromString('C')],
            false,
            new NotesRange('B1', 'B2')
        );
    }

    protected function getTranspositionFactory(): TranspositionFactory
    {
        if (empty($this->transpositionFactory)) {
            $this->transpositionFactory = new TranspositionFactory();
        }

        return $this->transpositionFactory;
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
        return $this->getTranspositionFactory()->createTransposition(
            $chords,
            $capo,
            $asBook,
            $offset,
            $range,
            $deviationFromCentered,
            $peopleRange
        );
    }

    public function test_calculate_centered_transposition()
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
            $this->buildTransposerWithValues()->calculateCenteredTransposition()
        );
    }

    public function test_find_centered_transposition_as_book()
    {
        $sut = $this->buildTransposer(
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

    public function test_calculate_equivalents_with_capo()
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

        $equivalents = $this->buildTransposerWithValues()->calculateEquivalentsWithCapo($testTransposition);

        $expected = [
            1=> $this->buildTransposition(['A#m', 'D#m', 'F#', 'C#'], 1, false),
            $this->buildTransposition(['Am', 'Dm', 'F', 'C'], 2, true),
            $this->buildTransposition(['G#m', 'C#m', 'E', 'B'], 3, false),
            $this->buildTransposition(['Gm', 'Cm', 'D#', 'A#'], 4, false),
            $this->buildTransposition(['F#m', 'Bm', 'D', 'A'], 5, false),
        ];

        $this->assertEquals($expected, $equivalents);
    }

    public function test_get_easier_not_equivalent()
    {
        $sut = $this->buildTransposer(
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

    public function test_force_highest_voice()
    {
        $sut = $this->buildTransposer(
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
            $sut->calculateCenteredTransposition(Transposer::FORCE_HIGHEST)
        );
    }

    public function test_force_lowest_voice()
    {
        $sut = $this->buildTransposer(
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
            $sut->calculateCenteredTransposition(Transposer::FORCE_LOWEST)
        );
    }

    public function test_people_compatible_no_data()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_already_compatible()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_wider_than_singer()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_wider_not_adjusted()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_wider_adjusted()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_adjusted_but_still_too_high()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_adjusted_well_high()
    {
        $sut = $this->buildTransposer(
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

    public function test_people_compatible_adjusted_well_low()
    {
        $sut = $this->buildTransposer(
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
