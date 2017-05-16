<?php

namespace NeoTransposer\Model;

class TranspositionChart
{
	/**
	 * @var \NeoTransposer\Model\NotesCalculator
	 */
	protected $nc;

	public function __construct(\NeoTransposer\Model\NotesCalculator $nc)
	{
		$this->nc = $nc;
	}
	/**
	 * @todo En vez de dos argumentos Transposition, un array con transposiciones ilimitadas, que tenga el objeto Transposition y todos los demás datos necesarios para imprimirlo, etc
	 */
	public function getChart(Song $song, Transposition $transposition, User $singer, Transposition $peopleCompatible = null)
	{
		$voiceChart = array(
			'singer' => array(
				'lowest'	=> $singer->lowest_note,
				'highest'	=> $singer->highest_note,
				'length'	=> abs($this->nc->distanceWithOctave($singer->lowest_note, $singer->highest_note)) - 1,
				'caption'	=> 'Your voice:',
				'css'		=> 'singer'
			),
			'original' => array(
				'lowest'	=> $song->lowestNote,
				'highest'	=> $song->highestNote,
				'length'	=> abs($this->nc->distanceWithOctave($song->lowestNote, $song->highestNote)) - 1,
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
				'length'	=> abs($this->nc->distanceWithOctave($peopleCompatible->lowestNote, $peopleCompatible->highestNote)) - 1,
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

		$min = $this->nc->lowestNote($lowestNotes);

		$nc = $this->nc;

		array_walk($voiceChart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $voiceChart;
	}
}
