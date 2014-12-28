<?php

use \NeoTransposer\NotesCalculator;

class NotesCalculatorTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var NotesCalculator
	 */
	protected $nc;

	public function setUp()
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
        $this->setExpectedException('Exception');
        $this->nc->readChord('Cmaj7');
    }

    public function transportChord()
    {
        $this->assertEquals(
            'D#m7',
            $this->nc->transportChord('C#m7', 2)
        );
    }

    public function testTransposeChords()
    {
        $this->assertEquals(
            array('Em', 'F#m', 'B79'),
            $this->nc->transposeChords(array('Am', 'Bm', 'E79'), 7)
        );
    }

    public function testGetAsOctaveDifference()
    {
    	$this->assertEquals('B +1', $this->nc->getAsOctaveDifference('B2'));
    }

    public function testGetOnlyNote()
    {
    	$this->assertEquals('B', $this->nc->getOnlyNote('B3'));
    }

    public function testLowestNote()
    {
    	$this->assertEquals('B1', $this->nc->lowestNote(array('B3', 'C2', 'B1')));
    }
}