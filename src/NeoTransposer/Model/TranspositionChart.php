<?php

namespace NeoTransposer\Model;

class TranspositionChart
{
	public static function getChart(Song $song, Transposition $transposition, User $singer)
	{
		$nc = new \NeoTransposer\Model\NotesCalculator;

		$voiceChart = array(
			'singer' => array(
				'lowest'	=> $singer->lowest_note,
				'highest'	=> $singer->highest_note,
				'length'	=> abs($nc->distanceWithOctave($singer->lowest_note, $singer->highest_note)) - 1,
				'caption'	=> 'Your voice:',
				'css'		=> 'singer'
			),
			'original' => array(
				'lowest'	=> $song->lowestNote,
				'highest'	=> $song->highestNote,
				'length'	=> abs($nc->distanceWithOctave($song->lowestNote, $song->highestNote)) - 1,
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

		$voiceChart['transposed']['length'] = $voiceChart['original']['length'];

		$min = $nc->lowestNote(array(
			$voiceChart['singer']['lowest'],
			$voiceChart['original']['lowest'],
			$voiceChart['transposed']['lowest'],
		));

		array_walk($voiceChart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $voiceChart;
	}
}