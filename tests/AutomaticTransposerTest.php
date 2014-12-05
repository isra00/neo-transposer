<?php

require_once 'AutomaticTransposer.php';
require_once 'Transposition.php';

/**
 * @todo Add some corner cases to transposition algorithms
 */
class AutomaticTransposerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * An instance of the class under test
	 * @var AutomaticTransposer
	 */
	protected $transposer;

    protected $testChords = array('G', 'Am', 'B7');

    public function setUp()
    {
        $this->transposer = new AutomaticTransposer;
    }

    public function testTransposeNote()
    {
        $this->assertEquals('C2', $this->transposer->transposeNote('B1', 1));
    }

    public function testDistanceWithOctave()
    {
        $this->assertEquals(-2, $this->transposer->distanceWithOctave('C1', 'D1'));
        $this->assertEquals(10, $this->transposer->distanceWithOctave('C2', 'D1'));
    }

    public function testReadChord()
    {
        $this->assertEquals(
            array('fundamental' => 'F#', 'attributes' => 'm79'),
            $this->transposer->readChord('F#m79')
        );
    }

    public function testReadChordNotRecognized()
    {
        $this->setExpectedException('Exception');
        $this->transposer->readChord('Cmaj7');
    }

    public function transportChord()
    {
        $this->assertEquals(
            'D#m7',
            $this->transposer->transportChord('C#m7', 2)
        );
    }

    public function testTransposeChords()
    {
        $this->assertEquals(
            array('Em', 'F#m', 'B79'),
            $this->transposer->transposeChords(array('Am', 'Bm', 'E79'), 7)
        );
    }

    public function testFindPerfectTransposition()
    {
        $result = $this->transposer->findPerfectTransposition(
            'C1',
            'C2',
            'G1',
            'G2',
            $this->testChords
        );

        $expected = new Transposition(array('C', 'Dm', 'E7'), 0, false, -7);

        $this->assertEquals($expected, $result);
    }

    public function testFindPerfectTranspositionAsBook()
    {
        $result = $this->transposer->findPerfectTransposition(
            'G1',
            'G2',
            'G1',
            'G2',
            $this->testChords
        );

        $expected = new Transposition($this->testChords, 0, true, 0);

        $this->assertEquals($expected, $result);
    }

    public function testFindEquivalentsWithCapo()
    {
        $testTransposition = new Transposition(array('C', 'Dm', 'E7'), 0, false, -7);
        $equivalents = $this->transposer->findEquivalentsWithCapo($testTransposition, $this->testChords);
        
        $expected = array(
            1=>new Transposition(array("B", "C#m", "D#7"), 1, false, -7),
            new Transposition(array("A#", "Cm", "D7"), 2, false, -7),
            new Transposition(array("A", "Bm", "C#7"), 3, false, -7),
            new Transposition(array("G#", "A#m", "C7"), 4, false, -7),
            new Transposition(array("G", "Am", "B7"), 5, true, -7)
        );

        $this->assertEquals($expected, $equivalents);
    }

    public function testSortTranspositionsByEase()
    {
        $a = new StdClass;
        $a->score = 10;
        $b = new StdClass;
        $b->score = 20;

        $this->assertEquals(
            array($a, $b),
            $this->transposer->sortTranspositionsByEase(array($b, $a))
        );
    }
}