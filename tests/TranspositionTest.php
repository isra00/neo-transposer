<?php

use \NeoTransposer\Transposition;

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
		$this->nc = new \NeoTransposer\NotesCalculator;
	}

	/*public function testGetWithAlternativeChords()
	{
		$expected = new Transposition(array('Em', 'Am', 'B7'));
		$this->assertEquals($expected, $this->transp->getWithAlternativeChords());
	}*/

	/**
	 * @todo Implement with a data provider
	 */
	public function testGetTone()
	{
		$this->assertEquals('G', $this->transp->getTone($this->nc));
		$this->assertEquals('G', (new Transposition(array('G7')))->getTone($this->nc));
		$this->assertEquals('F', (new Transposition(array('Dm5')))->getTone($this->nc));
	}

	public function testGetWithAlternativeChords()
	{
		$this->transp->setAlternativeChords($this->nc);
		$this->assertEquals(array('Em', 'Am', 'B7'), $this->transp->chords);
	}
}