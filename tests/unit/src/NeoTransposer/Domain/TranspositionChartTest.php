<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Domain\TranspositionChart;
use PHPUnit\Framework\TestCase;

class TranspositionChartTest extends TestCase
{
	public function testGetChart()
	{
		$mockNc = $this->getMockBuilder(NotesCalculator::class)
						->disableOriginalConstructor()
						->setMethods(['distanceWithOctave', 'lowestNote'])
						->getMock();

		//Exactly number of voices * 2 (1 for each voice + again in array_walk)
		$mockNc->expects($this->exactly(6))
				  ->method('distanceWithOctave')
				  ->will($this->returnValue(12));

		$mockNc->expects($this->exactly(1))
				  ->method('lowestNote')
				  ->will($this->returnValue('C1'));

		$mockSong = $this->getMockBuilder(Song::class)
						->disableOriginalConstructor()
						->getMock();

		$mockSong->range = new NotesRange('C1', 'C2');

		$mockUser = $this->getMockBuilder(User::class)
						->getMock();

		$mockUser->range = new NotesRange('C1', 'C2');

		$mockTransposition = $this->getMockBuilder(Transposition::class)
						->disableOriginalConstructor()
						->getMock();

		$mockTransposition->range = new NotesRange('C1', 'C2');

		/** @fixme Either make NotesNotation a non-static class or mock it here */
		$chart = new TranspositionChart($mockNc, $mockSong, $mockUser, 'american');
		$chart->addTransposition('testCaption', 'testCssClass', $mockTransposition);

		$expected = [
			[
				'caption' 			=> 'Your voice:',
				'css' 				=> 'singer',
				'lowest' 			=> 'C1',
				'highest' 			=> 'C2',
				'lowestForPrint' 	=> 'C1',
				'highestForPrint' 	=> 'C2',
				'length' 			=> 11,
				'offset' 			=> 12
			],
			[
				'caption' 			=> 'Original chords:',
				'css' 				=> 'original-song',
				'lowest' 			=> 'C1',
				'highest' 			=> 'C2',
				'lowestForPrint' 	=> 'C1',
				'highestForPrint' 	=> 'C2',
				'length' 			=> 11,
				'offset' 			=> 12
			],
			[
				'caption' 			=> 'testCaption',
				'css' 				=> 'testCssClass',
				'lowest' 			=> 'C1',
				'highest' 			=> 'C2',
				'lowestForPrint' 	=> 'C1',
				'highestForPrint' 	=> 'C2',
				'length' 			=> 11,
				'offset' 			=> 12
			],
		];

		$this->assertEquals($expected, $chart->getChartHtml());
	}
}
