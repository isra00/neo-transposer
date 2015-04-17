<?php

use \NeoTransposer\Model\AutomaticTransposer;
use \NeoTransposer\Model\Transposition;

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

    public function setUp()
    {
        $this->transposer = new AutomaticTransposer(
            'G1', 'G3', 'B1', 'B2', array('Am', 'Dm', 'F', 'C'), false
        );
    }

    public function testGetPerfectTransposition()
    {
        $expected = new Transposition(
            array('Bm', 'Em', 'G', 'D'),
            0,
            false,
            2,
            'C#2',
            'C#3',
            0
        );

        $this->assertEquals(
            $expected,
            $this->transposer->getPerfectTransposition()
        );
    }

    public function testFindPerfectTranspositionAsBook()
    {
        $transposer = new AutomaticTransposer(
            'F1', 'F3', 'B1', 'B2', array('Bm', 'Em', 'G', 'D'), false
        );

        $expected = new Transposition(
            array('Bm', 'Em', 'G', 'D'), 0, true, 0, 'B1', 'B2', 0
        );

        $this->assertEquals($expected, $transposer->getPerfectTransposition());
    }

    public function testFindEquivalentsWithCapo()
    {
        $testTransposition = new Transposition(array('Bm', 'Em', 'G', 'D'), 0, false);
        $equivalents = $this->transposer->findEquivalentsWithCapo($testTransposition);
        
        $expected = array(
            1=>new Transposition(array('A#m', 'D#m', 'F#', 'C#'), 1, false),
            new Transposition(array('Am', 'Dm', 'F', 'C'), 2, true),
            new Transposition(array('G#m', 'C#m', 'E', 'B'), 3, false),
            new Transposition(array('Gm', 'Cm', 'D#', 'A#'), 4, false),
            new Transposition(array('F#m', 'Bm', 'D', 'A'), 5, false)
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

    public function testFindAlternativeNotEquivalent()
    {
        $transposer = new AutomaticTransposer(
            'A1', 'D3', 'C#2', 'E3', array('D', 'F#', 'Bm', 'A', 'G'), false
        );

        $expected = new Transposition(
            array('C', 'E', 'Am', 'G', 'F'), 0, false, -2, 'B1', 'D3', 1, false
        );

        $this->assertEquals(
            $expected,
            $transposer->findAlternativeNotEquivalent()
        );
    }
}