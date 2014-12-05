<?php

require_once 'NotesCalculator.php';

class TranspositionChart
{
	public static function getChart($song_details, $transposition, AutomaticTransposer $transposer)
	{
		$voice_chart = array(
			'singer' => array(
				'lowest'	=> $_SESSION['lowest_note'],
				'highest'	=> $_SESSION['highest_note'],
				'length'	=> abs($transposer->distanceWithOctave($_SESSION['lowest_note'], $_SESSION['highest_note'])) - 1,
				'caption'	=> 'Your voice:',
				'css'		=> 'singer'
			),
			'original' => array(
				'lowest'	=> $song_details['lowest_note'],
				'highest'	=> $song_details['highest_note'],
				'length'	=> abs($transposer->distanceWithOctave($song_details['lowest_note'], $song_details['highest_note'])) - 1,
				'caption'	=> 'Original song:',
				'css'		=> 'original-song'
			),
			'transposed' => array(
				'lowest'	=> $transposer->transposeNote($song_details['lowest_note'], $transposition->offset),
				'highest'	=> $transposer->transposeNote($song_details['highest_note'], $transposition->offset),
				'caption'	=> 'Transposed:',
				'css'		=> 'transposed-song'
			),
		);

		$voice_chart['transposed']['length'] = $voice_chart['original']['length'];

		$nc = new NotesCalculator;

		$min = $nc->lowestNote(array(
			$voice_chart['singer']['lowest'],
			$voice_chart['original']['lowest'],
			$voice_chart['transposed']['lowest'],
		));

		array_walk($voice_chart, function(&$voice) use ($min, $transposer) {
			$voice['offset'] = abs($transposer->distanceWithOctave($min, $voice['lowest']));
		});

		return $voice_chart;
	}
}