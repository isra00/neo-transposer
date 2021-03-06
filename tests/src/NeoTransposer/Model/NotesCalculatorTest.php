<?php

namespace NeoTransposer\Tests\Model;

use \NeoTransposer\Model\NotesCalculator;

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

    public function testReadChord()
    {
        $this->assertEquals(
            array('fundamental' => 'F#', 'attributes' => 'm79'),
            $this->nc->readChord('F#m79')
        );
    }

    public function testReadChordNotRecognized()
    {
        $this->expectException('Exception');
        $this->nc->readChord('Cmaj7');
    }

    public function testTransposeChord()
    {
        $this->assertEquals(
            'D#m7',
            $this->nc->transposeChord('C#m7', 2)
        );
    }

    public function testTransposeChords()
    {
        $this->assertEquals(
            array('Em', 'F#m', 'B79'),
            $this->nc->transposeChords(array('Am', 'Bm', 'E79'), 7)
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
}