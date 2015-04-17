<?php

namespace NeoTransposer\Model;

class TranspositionChart
{
	public static function getChart($songDetails, $transposition, $singer)
	{
		$nc = new \NeoTransposer\Model\NotesCalculator;

		$voice_chart = array(
			'singer' => array(
				'lowest'	=> $singer->lowest_note,
				'highest'	=> $singer->highest_note,
				'length'	=> abs($nc->distanceWithOctave($singer->lowest_note, $singer->highest_note)) - 1,
				'caption'	=> 'Your voice:',
				'css'		=> 'singer'
			),
			'original' => array(
				'lowest'	=> $songDetails['lowest_note'],
				'highest'	=> $songDetails['highest_note'],
				'length'	=> abs($nc->distanceWithOctave($songDetails['lowest_note'], $songDetails['highest_note'])) - 1,
				'caption'	=> 'Original chords:',
				'css'		=> 'original-song'
			),
			'transposed' => array(
				'lowest'	=> $transposition->lowestNote,
				'highest'	=> $transposition->highestNote,
				'caption'	=> 'Transposed:',
				'css'		=> 'transposed-song'
			),
		);

		$voice_chart['transposed']['length'] = $voice_chart['original']['length'];

		$min = $nc->lowestNote(array(
			$voice_chart['singer']['lowest'],
			$voice_chart['original']['lowest'],
			$voice_chart['transposed']['lowest'],
		));

		array_walk($voice_chart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $voice_chart;
	}
}