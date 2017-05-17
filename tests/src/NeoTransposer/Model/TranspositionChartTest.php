<?php

use \NeoTransposer\Model\{TranspositionChart, AutomaticTransposer, Song, User, Transposition};

class TranspositionChartTest extends PHPUnit_Framework_TestCase
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

		$mockSong->lowestNote 	= 'C1';
		$mockSong->highestNote 	= 'C2';

		$mockUser = $this->getMockBuilder(\NeoTransposer\Model\User::class)
						->getMock();

		$mockUser->lowest_note	= 'C1';
		$mockUser->highest_note	= 'C2';

		$mockTransposition = $this->getMockBuilder(\NeoTransposer\Model\Transposition::class)
						->disableOriginalConstructor()
						->getMock();

		$mockTransposition->lowestNote 	= 'C1';
		$mockTransposition->highestNote = 'C2';

		$chart = new TranspositionChart($mockNc, $mockSong, $mockUser);
		$chart->addTransposition('testCaption', 'testCssClass', $mockTransposition);

		$expected = [
			[
				'caption' 	=> 'Your voice:', 
				'css' 		=> 'singer', 
				'lowest' 	=> 'C1', 
				'highest' 	=> 'C2', 
				'length' 	=> 11, 
				'offset' 	=> 12
			],
			[
				'caption' 	=> 'Original chords:', 
				'css' 		=> 'original-song', 
				'lowest' 	=> 'C1', 
				'highest' 	=> 'C2', 
				'length' 	=> 11, 
				'offset' 	=> 12
			],
			[
				'caption' 	=> 'testCaption', 
				'css' 		=> 'testCssClass', 
				'lowest' 	=> 'C1', 
				'highest' 	=> 'C2', 
				'length' 	=> 11, 
				'offset' 	=> 12
			],
		];

		$this->assertEquals($expected, $chart->getChart());
	}
}