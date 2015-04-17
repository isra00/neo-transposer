<?php

use \NeoTransposer\Model\Transposition;
use \NeoTransposer\Model\NotesCalculator;

class TranspositionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var Transposition
	 */
	protected $transp;

	/**
	 * An instance of NotesCalculator, needed by some methods.
	 * @var \NeoTransposer\NotesCalculator;
	 */
	protected $nc;

	public function setUp()
	{
		$this->transp = new Transposition(array('Em', 'Am', 'B'));
		$this->nc = new NotesCalculator;
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
		$this->assertEquals('G', (new Transposition(array('G7')))->getKey($this->nc));
		$this->assertEquals('F', (new Transposition(array('Dm5')))->getKey($this->nc));
	}

	public function testGetWithAlternativeChords()
	{
		$this->transp->setAlternativeChords($this->nc);
		$this->assertEquals(array('Em', 'Am', 'B7'), $this->transp->chords);

		// If AsBook, alternative chords should not be calculated.
		$chords2 = array('Em', 'Am', 'B');
		$tr2 = new Transposition($chords2, 0, true);
		$tr2->setAlternativeChords($this->nc);
		$this->assertEquals($chords2, $tr2->chords);
	}
}