<?php

namespace NeoTransposer\Model;

class TranspositionChart
{
	/**
	 * @var \NeoTransposer\Model\NotesCalculator
	 */
	protected $nc;

	/**
	 * @var array
	 */
	protected $voiceChart = [];

	public function __construct(\NeoTransposer\Model\NotesCalculator $nc, Song $song, User $singer)
	{
		$this->nc = $nc;

		$this->addVoice('Your voice:', 'singer', $singer->range);
		$this->addVoice('Original chords:', 'original-song', $song->range);
	}

	public function addVoice($caption, $cssClass, NotesRange $range)
	{
		$this->voiceChart[] = [
			'caption'	=> $caption,
			'css'		=> $cssClass,
			'lowest'	=> $range->lowest,
			'highest'	=> $range->highest,
			'length'	=> abs($this->nc->distanceWithOctave($range->lowest, $range->highest)) - 1,
		];
	}

	public function addTransposition($caption, $cssClass, Transposition $transposition)
	{
		$this->addVoice($caption, $cssClass, $transposition->range);
	}
	
	public function getChart()
	{
		$min = $this->nc->lowestNote(array_column($this->voiceChart, 'lowest'));

		$nc  = $this->nc;

		array_walk($this->voiceChart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $this->voiceChart;
	}
}
