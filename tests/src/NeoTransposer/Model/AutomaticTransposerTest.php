<?php

use \NeoTransposer\Model\{
	AutomaticTransposer, 
	Transposition, 
	NotesRange, 
	PeopleCompatibleTransposition, 
	PeopleCompatibleCalculation
};

/**
 * @todo Add some corner cases to transposition algorithms
 */
class AutomaticTransposerTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * An instance of the class under test
	 * @var AutomaticTransposer
	 */
	protected $transposer;

	protected $chordsScoreConfig;

	protected $app;

	public function setUp() : void
	{
		//includePath must be defined in phpunit.xml
		$this->chordsScoreConfig = include './config.scores.php';

		$this->transposer = new AutomaticTransposer($this->getSilexApp());
		$this->transposer->setTransposerData(
			new NotesRange('G1', 'G3'), 
			new NotesRange('B1', 'B2'), 
			['Am', 'Dm', 'F', 'C'], 
			false, 
			new NotesRange('B1', 'B2')
		);
	}

	protected function getSilexApp()
	{
		if (empty($this->app))
		{
			$this->app = new \Silex\Application;
			$this->app['neoconfig'] = [
				'chord_scores' => $this->chordsScoreConfig,
				'people_range' => ['B1', 'B2'],
			];

			$this->app['new.Transposition'] = $this->app->factory(function ($app) {
				return new \NeoTransposer\Model\Transposition($app);
			});

			$this->app['new.PeopleCompatibleTransposition'] = $this->app->factory(function ($app) {
				return new \NeoTransposer\Model\PeopleCompatibleTransposition($app);
			});
		}

		return $this->app;
	}

	protected function getNewTransposition()
	{
		return new \NeoTransposer\Model\Transposition($this->app);
	}

	public function testCalculateCenteredTransposition()
	{
		$expected = $this->getNewTransposition();
		$expected->setTranspositionData(
			['Bm', 'Em', 'G', 'D'],
			0,
			false,
			2,
			new NotesRange('C#2', 'C#3'),
			0
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculateCenteredTransposition()
		);
	}

	public function testFindCenteredTranspositionAsBook()
	{
		$this->transposer->setTransposerData(
			new NotesRange('F1', 'F3'), new NotesRange('B1', 'B2'), ['Bm', 'Em', 'G', 'D'], false, new NotesRange('B1', 'B2')
		);

		$expected = $this->getNewTransposition();
		$expected->setTranspositionData(
			['Bm', 'Em', 'G', 'D'], 0, true, 0, new NotesRange('B1', 'B2'), 0, 'B1', 'B2'
		);

		$this->assertEquals($expected, $this->transposer->calculateCenteredTransposition());
	}

	public function testCalculateEquivalentsWithCapo()
	{
		$testTransposition = $this->getNewTransposition();
		$testTransposition->setTranspositionData(['Bm', 'Em', 'G', 'D'], 0, false, null, null, null, null, 'B1', 'B2');
		
		$expected = [
			1=> $this->getNewTransposition()->setTranspositionData(['A#m', 'D#m', 'F#', 'C#'], 1, false),
			$this->getNewTransposition()->setTranspositionData(['Am', 'Dm', 'F', 'C'], 2, true),
			$this->getNewTransposition()->setTranspositionData(['G#m', 'C#m', 'E', 'B'], 3, false),
			$this->getNewTransposition()->setTranspositionData(['Gm', 'Cm', 'D#', 'A#'], 4, false),
			$this->getNewTransposition()->setTranspositionData(['F#m', 'Bm', 'D', 'A'], 5, false)
		];

		$equivalents = $this->transposer->calculateEquivalentsWithCapo($testTransposition);

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
			[$transpositionMockA, $transpositionMockB],
			$this->transposer->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
		);
	}

	public function testSortTranspositionsByEaseWhenEqualScorePrioritizeAsBook()
	{
		$transpositionMockA = $this->getMockBuilder(Transposition::class)
				  ->disableOriginalConstructor()
				  ->setMethods(['trans'])
				  ->getMock();

		$transpositionMockB = clone $transpositionMockA;

		$transpositionMockA->score = 10;
		$transpositionMockB->score = 10;

		$transpositionMockA->setAsBook(true);

		$this->assertEquals(
			[$transpositionMockA, $transpositionMockB],
			$this->transposer->sortTranspositionsByEase([$transpositionMockB, $transpositionMockA])
		);
	}

	public function testCalculateAlternativeNotEquivalent()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'D3'), new NotesRange('C#2', 'E3'), ['D', 'F#', 'Bm', 'A', 'G'], false, new NotesRange('B1', 'B2')
		);

		$actual = $this->transposer->calculateAlternativeNotEquivalent();

		$expected = $this->getNewTransposition();
		$expected->setTranspositionData(
			['C', 'E', 'Am', 'G', 'F'], 0, false, -2, new NotesRange('B1', 'D3'), 1
		);

		$this->assertEquals(
			$expected,
			$actual
		);
	}

	public function testForceHighestVoice()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], false, new NotesRange('B1', 'B2')
		);

		$expected = $this->getNewTransposition();
		$expected->setTranspositionData(
			['Em', 'D'], 0, false, 7, new NotesRange('B2', 'E3'), 0
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculateCenteredTransposition(AutomaticTransposer::FORCE_HIGHEST)
		);
	}

	public function testForceLowestVoice()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], false, new NotesRange('B1', 'B2')
		);

		$expected = $this->getNewTransposition();
		$expected->setTranspositionData(['Dm', 'C'], 0, false, -7, new NotesRange('A1', 'D2'));

		$this->assertEquals(
			$expected,
			$this->transposer->calculateCenteredTransposition(AutomaticTransposer::FORCE_LOWEST)
		);
	}

	public function testPeopleCompatibleNoData()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('E2', 'A2'), ['Am', 'G'], true
		);

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA, 
			null
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleAlreadyCompatible()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('A2', 'F3'), ['Am', 'E'], true, new NotesRange('A2', 'D3')
		);

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::ALREADY_COMPATIBLE, 
			null
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleWiderThanSinger()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('A1', 'F3'), ['Am', 'E'], true, new NotesRange('A2', 'D3')
		);

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::WIDER_THAN_SINGER, 
			null
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleWiderNotAdjusted()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('D2', 'F#3'), ['Em', 'D'], true, new NotesRange('D2', 'E3')
		);

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER, 
			null
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleWiderAdjusted()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), new NotesRange('A1', 'D3'), ['Am', 'E'], true, new NotesRange('A1', 'D3')
		);

		$ppc = new \NeoTransposer\Model\PeopleCompatibleTransposition($this->app);
		$ppc->setTranspositionData(['Am', 'E'], 2, true, 2, new NotesRange('B1', 'E3'), 1);
		$ppc->peopleRange = new NotesRange('B1', 'E3');

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::ADJUSTED_WIDER, 
			$ppc
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleAdjustedButStillTooHigh()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), 
			new NotesRange('A1', 'D3'), 
			['Am', 'Dm'], 
			true, 
			new NotesRange('G#2', 'D3')
		);

		$ppc = new \NeoTransposer\Model\PeopleCompatibleTransposition($this->app);
		$ppc->setTranspositionData(['Em', 'Am'], 5, false, 0, new NotesRange('A1', 'D3'), -1);
		$ppc->peopleRange = new NotesRange('G#2', 'D3');

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE, 
			$ppc
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleAdjustedWellHigh()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), 
			new NotesRange('B1', 'B2'), 
			['D', 'Em'], 
			false, 
			new NotesRange('B1', 'B2')
		);

		$ppc = new \NeoTransposer\Model\PeopleCompatibleTransposition($this->app);
		$ppc->setTranspositionData(['D', 'Em'], 0, true, 0, new NotesRange('B1', 'B2'), -2);
		$ppc->peopleRange = new NotesRange('B1', 'B2');

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::ADJUSTED_WELL, 
			$ppc
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}

	public function testPeopleCompatibleAdjustedWellLow()
	{
		$this->transposer->setTransposerData(
			new NotesRange('A1', 'E3'), 
			new NotesRange('B1', 'E3'), 
			['Am', 'Dm', 'E'], 
			true, 
			new NotesRange('B1', 'F2')
		);

		$ppc = new \NeoTransposer\Model\PeopleCompatibleTransposition($this->app);
		$ppc->setTranspositionData(['Am', 'Dm', 'E'], 0, true, 0, new NotesRange('B1', 'E3'), 1);
		$ppc->peopleRange = new NotesRange('B1', 'F2');

		$expected = new PeopleCompatibleCalculation(
			PeopleCompatibleCalculation::ADJUSTED_WELL, 
			$ppc
		);

		$this->assertEquals(
			$expected,
			$this->transposer->calculatePeopleCompatible()
		);
	}
}