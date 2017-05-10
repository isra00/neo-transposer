<?php

namespace NeoTransposer\Model;

class TranspositionChart
{
	/**
	 * @todo Avoid static stuff!
	 * @todo En vez de dos argumentos Transposition, un array con transposiciones ilimitadas, que tenga el objeto Transposition y todos los demás datos necesarios para imprimirlo, etc
	 */
	public static function getChart(Song $song, Transposition $transposition, User $singer, Transposition $peopleCompatible = null)
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

		if ($peopleCompatible)
		{
			$voiceChart['peopleCompatible'] = array(
				'lowest'	=> $peopleCompatible->lowestNote,
				'highest'	=> $peopleCompatible->highestNote,
				'peopleLowest'	=> $peopleCompatible->peopleLowestNote,
				'peopleHighest'	=> $peopleCompatible->peopleHighestNote,
				'length'	=> abs($nc->distanceWithOctave($peopleCompatible->lowestNote, $peopleCompatible->highestNote)) - 1,
				'caption'	=> 'People:',
				'css'		=> 'people-compatible'
			);
		}

		$voiceChart['transposed']['length'] = $voiceChart['original']['length'];

		/** @todo Esto se puede simplificar con array_column (incluyendo el peopleCompatible)? */
		$lowestNotes = array(
			$voiceChart['singer']['lowest'],
			$voiceChart['original']['lowest'],
			$voiceChart['transposed']['lowest'],
		);

		if (isset($voiceChart['peopleCompatible']))
		{
			$lowestNotes[] = $voiceChart['peopleCompatible']['lowest'];
		}

		$min = $nc->lowestNote($lowestNotes);

		array_walk($voiceChart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $voiceChart;
	}
}
