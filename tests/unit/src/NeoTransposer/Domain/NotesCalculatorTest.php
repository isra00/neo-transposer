<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

class NotesCalculatorTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var NotesCalculator
	 */
	protected $notesCalculator;

	public function setUp() : void
	{
		$this->notesCalculator = new NotesCalculator();
	}

	public function testTransposeNote()
    {
        $this->assertEquals('C2', $this->notesCalculator->transposeNote('B1', 1));
    }

    public function testDistanceWithOctave()
    {
        $this->assertEquals(-2, $this->notesCalculator->distanceWithOctave('C1', 'D1'));
        $this->assertEquals(10, $this->notesCalculator->distanceWithOctave('C2', 'D1'));
    }

    public function testTransposeChord()
    {
        $this->assertEquals(
            Chord::fromString('D#m7'),
            $this->notesCalculator->transposeChord(Chord::fromString('C#m7'), 2)
        );
    }

    public function testTransposeChords()
    {
        $this->assertEquals(
            array(Chord::fromString('Em'), Chord::fromString('F#m'), Chord::fromString('B79')),
            $this->notesCalculator->transposeChords(array(Chord::fromString('Am'), Chord::fromString('Bm'), Chord::fromString('E79')), 7)
        );
    }

    public function testLowestNote()
    {
        $this->assertEquals('B1', $this->notesCalculator->lowestNote(array('B3', 'C2', 'B1')));
    }

    public function testLowestNoteInvalidNote()
    {
        $this->expectException(\InvalidArgumentException::class);
    	$this->notesCalculator->lowestNote(array('H1', 'C2'));
    }

    public function testArrayIndex()
    {
        $arr = array('a', 'b', 'c', 'd');

        $this->assertEquals('a', $this->notesCalculator->arrayIndex($arr, 4));
        $this->assertEquals('a', $this->notesCalculator->arrayIndex($arr, 8));
        $this->assertEquals('d', $this->notesCalculator->arrayIndex($arr, -1));
        $this->assertEquals('d', $this->notesCalculator->arrayIndex($arr, -5));
    }

    public function testTransposeRange()
    {
        $this->assertEquals(
            new NotesRange('B1', 'E1'),
            $this->notesCalculator->transposeRange(new NotesRange('A1', 'D1'), 2)
        );
    }

    public function testRangeWideness()
    {
        $this->assertEquals(
            14,
            $this->notesCalculator->rangeWideness(new NotesRange('A1', 'B2'))
        );
    }

    /**
     * @dataProvider providerGetKey
     */
    public function testGetKey($chord, $expectedKey)
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
