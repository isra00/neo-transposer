<?php

namespace NeoTransposer\Tests\Model;

use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Model\{TranspositionChart};

class TranspositionChartTest extends \PHPUnit\Framework\TestCase
{
	public function testGetChart()
	{
		$mockNc = $this->getMockBuilder(\NeoTransposer\Model\NotesCalculator::class)
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

		$mockSong = $this->getMockBuilder(\NeoTransposer\Model\Song::class)
						->disableOriginalConstructor()
						->getMock();

		$mockSong->range = new NotesRange('C1', 'C2');

		$mockUser = $this->getMockBuilder(\NeoTransposer\Model\User::class)
						->getMock();

		$mockUser->range = new NotesRange('C1', 'C2');

		$mockTransposition = $this->getMockBuilder(\NeoTransposer\Model\Transposition::class)
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