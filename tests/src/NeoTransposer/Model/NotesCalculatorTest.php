<?php

namespace NeoTransposer\Tests\Model;

use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Model\NotesCalculator;
use NeoTransposer\Model\NotesRange;

class NotesCalculatorTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var NotesCalculator
	 */
	protected $nc;

	public function setUp() : void
	{
		$this->nc = new NotesCalculator;
	}

	public function testTransposeNote()
    {
        $this->assertEquals('C2', $this->nc->transposeNote('B1', 1));
    }

    public function testDistanceWithOctave()
    {
        $this->assertEquals(-2, $this->nc->distanceWithOctave('C1', 'D1'));
        $this->assertEquals(10, $this->nc->distanceWithOctave('C2', 'D1'));
    }

    public function testTransposeChord()
    {
        $this->assertEquals(
            Chord::fromString('D#m7'),
            $this->nc->transposeChord(Chord::fromString('C#m7'), 2)
        );
    }

    public function testTransposeChords()
    {
        $this->assertEquals(
            array(Chord::fromString('Em'), Chord::fromString('F#m'), Chord::fromString('B79')),
            $this->nc->transposeChords(array(Chord::fromString('Am'), Chord::fromString('Bm'), Chord::fromString('E79')), 7)
        );
    }

    public function testLowestNote()
    {
        $this->assertEquals('B1', $this->nc->lowestNote(array('B3', 'C2', 'B1')));
    }

    public function testLowestNoteInvalidNote()
    {
        $this->expectException(\InvalidArgumentException::class);
    	$this->nc->lowestNote(array('H1', 'C2'));
    }

    public function testArrayIndex()
    {
        $arr = array('a', 'b', 'c', 'd');

        $this->assertEquals('a', $this->nc->arrayIndex($arr, 4));
        $this->assertEquals('a', $this->nc->arrayIndex($arr, 8));
        $this->assertEquals('d', $this->nc->arrayIndex($arr, -1));
        $this->assertEquals('d', $this->nc->arrayIndex($arr, -5));
    }

    public function testTransposeRange()
    {
        $this->assertEquals(
            new NotesRange('B1', 'E1'),
            $this->nc->transposeRange(new NotesRange('A1', 'D1'), 2)
        );
    }

    public function testRangeWideness()
    {
        $this->assertEquals(
            14,
            $this->nc->rangeWideness(new NotesRange('A1', 'B2'))
        );
    }
}