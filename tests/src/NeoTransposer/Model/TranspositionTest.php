<?php

namespace NeoTransposer\Tests\Model;

use \NeoTransposer\Model\{Transposition, NotesCalculator};

class TranspositionTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var Transposition
	 */
	protected $transp;

	protected $chordsScoreConfig;

	/**
	 * An instance of NotesCalculator, needed by some methods.
	 * @var \NeoTransposer\NotesCalculator;
	 */
	protected $nc;

	protected $app;

	public function setUp() : void
	{
        //root dir should be in includePath from phpunit.xml
        $this->chordsScoreConfig = include './config.scores.php';

		$this->transp = new Transposition($this->getSilexApp());
		$this->transp->setTranspositionData(
			['Em', 'Am', 'B'], null, null, null, null, null, null, $this->chordsScoreConfig
		);

		$this->nc = new NotesCalculator;
	}

	protected function getSilexApp()
	{
		if (empty($this->app))
		{
			$this->app = new \Silex\Application;
			$this->app['neoconfig'] = ['chord_scores' => $this->chordsScoreConfig];
		}

		return $this->app;
	}

	protected function getNewTransposition()
	{
		return new \NeoTransposer\Model\Transposition($this->app);
	}

	/*public function testGetWithAlternativeChords()
	{
		$expected = new Transposition(array('Em', 'Am', 'B7'));
		$this->assertEquals($expected, $this->transp->getWithAlternativeChords());
	}*/

	/**
	 * @todo Implement with a data provider
	 */
	public function testGetKey()
	{
		$this->assertEquals('G', $this->transp->getKey($this->nc));
		$this->assertEquals('G', ($this->getNewTransposition()->setTranspositionData(['G7'], null, null, null, null, null, null, $this->chordsScoreConfig))->getKey($this->nc));
		$this->assertEquals('F', ($this->getNewTransposition()->setTranspositionData(['Dm5'], null, null, null, null, null, null, $this->chordsScoreConfig))->getKey($this->nc));
	}

	public function testGetWithAlternativeChords()
	{
		$this->transp->setAlternativeChords($this->nc);
		$this->assertEquals(array('Em', 'Am', 'B7'), $this->transp->chords);

		// If AsBook, alternative chords should not be calculated.
		$chords2 = array('Em', 'Am', 'B');
		$tr2 = $this->getNewTransposition()->setTranspositionData($chords2, 0, true, null, null, null, null, $this->chordsScoreConfig);
		$tr2->setAlternativeChords($this->nc);
		$this->assertEquals($chords2, $tr2->chords);
	}
}