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

    protected $chordsScoreConfig;

    public function setUp()
    {
        //root dir should be in includePath from phpunit.xml
        $this->chordsScoreConfig = include './config.scores.php';

        $this->transposer = new AutomaticTransposer(
            'G1', 'G3', 'B1', 'B2', array('Am', 'Dm', 'F', 'C'), false, $this->chordsScoreConfig
        );
    }

    public function testGetCenteredTransposition()
    {
        $expected = new Transposition(
            array('Bm', 'Em', 'G', 'D'),
            0,
            false,
            2,
            'C#2',
            'C#3',
            0,
            $this->chordsScoreConfig
        );

        $this->assertEquals(
            $expected,
            $this->transposer->getCenteredTransposition()
        );
    }

    public function testFindCenteredTranspositionAsBook()
    {
        $transposer = new AutomaticTransposer(
            'F1', 'F3', 'B1', 'B2', array('Bm', 'Em', 'G', 'D'), false, $this->chordsScoreConfig
        );

        $expected = new Transposition(
            array('Bm', 'Em', 'G', 'D'), 0, true, 0, 'B1', 'B2', 0, $this->chordsScoreConfig
        );

        $this->assertEquals($expected, $transposer->getCenteredTransposition());
    }

    public function testFindEquivalentsWithCapo()
    {
        $testTransposition = new Transposition(array('Bm', 'Em', 'G', 'D'), 0, false, null, null, null, null, $this->chordsScoreConfig);
        $equivalents = $this->transposer->findEquivalentsWithCapo($testTransposition);
        
        $expected = array(
            1=>new Transposition(array('A#m', 'D#m', 'F#', 'C#'), 1, false, null, null, null, null, $this->chordsScoreConfig),
            new Transposition(array('Am', 'Dm', 'F', 'C'), 2, true, null, null, null, null, $this->chordsScoreConfig),
            new Transposition(array('G#m', 'C#m', 'E', 'B'), 3, false, null, null, null, null, $this->chordsScoreConfig),
            new Transposition(array('Gm', 'Cm', 'D#', 'A#'), 4, false, null, null, null, null, $this->chordsScoreConfig),
            new Transposition(array('F#m', 'Bm', 'D', 'A'), 5, false, null, null, null, null, $this->chordsScoreConfig)
        );

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
            array($transpositionMockA, $transpositionMockB),
            $this->transposer->sortTranspositionsByEase(array($transpositionMockB, $transpositionMockA))
        );
    }

    public function testFindAlternativeNotEquivalent()
    {
        $transposer = new AutomaticTransposer(
            'A1', 'D3', 'C#2', 'E3', array('D', 'F#', 'Bm', 'A', 'G'), false, $this->chordsScoreConfig
        );

        $expected = new Transposition(
            array('C', 'E', 'Am', 'G', 'F'), 0, false, -2, 'B1', 'D3', 1, $this->chordsScoreConfig
        );

        $this->assertEquals(
            $expected,
            $transposer->findAlternativeNotEquivalent()
        );
    }

    public function testForceHighestVoice()
    {
        $transposer = new AutomaticTransposer(
            'A1', 'E3', 'E2', 'A2', array('Am', 'G'), false, $this->chordsScoreConfig
        );

        $expected = new Transposition(
            array('Em', 'D'), 0, false, 7, 'B2', 'E3', 0, $this->chordsScoreConfig
        );

        $this->assertEquals(
            $expected,
            $transposer->getCenteredTransposition(AutomaticTransposer::FORCE_HIGHEST)
        );
    }

    public function testForceLowestVoice()
    {
        $transposer = new AutomaticTransposer(
            'A1', 'E3', 'E2', 'A2', array('Am', 'G'), false, $this->chordsScoreConfig
        );

        $expected = new Transposition(
            array('Dm', 'C'), 0, false, -7, 'A1', 'D2', 0, $this->chordsScoreConfig
        );

        $this->assertEquals(
            $expected,
            $transposer->getCenteredTransposition(AutomaticTransposer::FORCE_LOWEST)
        );
    }
}