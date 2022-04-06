<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Silex\Application;

class TranspositionTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Fixture of the SUT.
	 * @var Transposition
	 */
	protected $sut;

	/**
	 * An instance of NotesCalculator, needed by some methods.
	 * @var NotesCalculator;
	 */
	protected $nc;

	protected $dependencyContainer;

	public function setUp() : void
	{
		$this->sut = new Transposition($this->getDependencyContainer());
		$this->sut->setTranspositionData(
			['Em', 'Am', 'B'],
            null,
            null,
            null,
            null,
            null,
            null
		);

		$this->nc = new NotesCalculator();
	}

	protected function getDependencyContainer(): Application
	{
		if (empty($this->dependencyContainer))
		{
			$this->dependencyContainer = new Application();
			$this->dependencyContainer['neoconfig'] = ['chord_scores' => include __DIR__ . '/../../../../config.scores.php'];
		}

		return $this->dependencyContainer;
	}

	protected function createEmptyTransposition()
	{
		return new Transposition($this->dependencyContainer);
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
		$this->assertEquals('G', $this->sut->getKey($this->nc));
		$this->assertEquals('G', ($this->createEmptyTransposition()->setTranspositionData(['G7'], null, null, null, null, null, null))->getKey($this->nc));
		$this->assertEquals('F', ($this->createEmptyTransposition()->setTranspositionData(['Dm5'], null, null, null, null, null, null))->getKey($this->nc));
	}

	public function testGetWithAlternativeChords()
	{
		$this->sut->setAlternativeChords($this->nc);
		$this->assertEquals(array('Em', 'Am', 'B7'), $this->sut->chords);

		// If AsBook, alternative chords should not be calculated.
		$chords2 = array('Em', 'Am', 'B');
		$tr2 = $this->createEmptyTransposition()->setTranspositionData($chords2, 0, true, null, null, null, null);
		$tr2->setAlternativeChords($this->nc);
		$this->assertEquals($chords2, $tr2->chords);
	}

    public function testCalculatePeopleRange()
    {
        $this->sut->calculatePeopleRange(
            new NotesRange('A1', 'A2'),
            2,
            $this->nc
        );

        $this->assertEquals(new NotesRange('B1', 'B2'), $this->sut->peopleRange);
    }
}