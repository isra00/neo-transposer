<?php

namespace NeoTransposer\Tests\Domain;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

class NotesCalculatorTest extends TestCase
{
    /**
     * Fixture of the SUT.
     *
     * @var NotesCalculator
     */
    protected $notesCalculator;

    protected function setUp(): void
    {
        $this->notesCalculator = new NotesCalculator();
    }

    public function test_transpose_note()
    {
        $this->assertEquals('C2', $this->notesCalculator->transposeNote('B1', 1));
    }

    public function test_distance_with_octave()
    {
        $this->assertEquals(-2, $this->notesCalculator->distanceWithOctave('C1', 'D1'));
        $this->assertEquals(10, $this->notesCalculator->distanceWithOctave('C2', 'D1'));
    }

    public function test_transpose_chord()
    {
        $this->assertEquals(
            Chord::fromString('D#m7'),
            $this->notesCalculator->transposeChord(Chord::fromString('C#m7'), 2)
        );
    }

    public function test_transpose_chords()
    {
        $this->assertEquals(
            [Chord::fromString('Em'), Chord::fromString('F#m'), Chord::fromString('B79')],
            $this->notesCalculator->transposeChords([Chord::fromString('Am'), Chord::fromString('Bm'), Chord::fromString('E79')], 7)
        );
    }

    public function test_lowest_note()
    {
        $this->assertEquals('B1', $this->notesCalculator->lowestNote(['B3', 'C2', 'B1']));
    }

    public function test_lowest_note_invalid_note()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->notesCalculator->lowestNote(['H1', 'C2']);
    }

    public function test_array_index()
    {
        $arr = ['a', 'b', 'c', 'd'];

        $this->assertEquals('a', $this->notesCalculator->arrayIndex($arr, 4));
        $this->assertEquals('a', $this->notesCalculator->arrayIndex($arr, 8));
        $this->assertEquals('d', $this->notesCalculator->arrayIndex($arr, -1));
        $this->assertEquals('d', $this->notesCalculator->arrayIndex($arr, -5));
    }

    public function test_transpose_range()
    {
        $this->assertEquals(
            new NotesRange('B1', 'E1'),
            $this->notesCalculator->transposeRange(new NotesRange('A1', 'D1'), 2)
        );
    }

    public function test_range_wideness()
    {
        $this->assertEquals(
            14,
            $this->notesCalculator->rangeWideness(new NotesRange('A1', 'B2'))
        );
    }

    /**
     * @dataProvider providerGetKey
     */
    public function test_get_key($chord, $expectedKey)
    {
        $this->assertEquals(
            $expectedKey,
            $this->notesCalculator->getKey(Chord::fromString($chord))
        );
    }

    public function providerGetKey(): array
    {
        return [
            ['Em', 'G'],
            ['G7', 'G'],
            ['Dm5', 'F'],
        ];
    }
}
