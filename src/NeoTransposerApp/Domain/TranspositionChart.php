<?php

namespace NeoTransposerApp\Domain;

use NeoTransposerApp\Domain\Entity\Song;
use NeoTransposerApp\Domain\Entity\User;
use NeoTransposerApp\Domain\ValueObject\NotesRange;

class TranspositionChart
{
	/**
	 * @var NotesCalculator
	 */
	protected $nc;

	/**
	 * @var array
	 */
	protected $voiceChart = [];

	/**
	 * Notation for printing notes
	 * @var string
	 */
	protected $notation = '';

	public function __construct(NotesCalculator $nc, Song $song, User $singer, $notation)
	{
		$this->nc = $nc;
		$this->notation = $notation;

		$this->addVoice('Your voice:', 'singer', $singer->range);
		$this->addVoice('Original chords:', 'original-song', $song->range);
	}

	public function addVoice(string $caption, string $cssClass, NotesRange $range): void
	{
        $notesNotation = new NotesNotation();
        $this->voiceChart[] = [
            'caption'         => $caption,
            'css'             => $cssClass,
            'lowest'          => $range->lowest(),
            'highest'         => $range->highest(),
            'lowestForPrint'  => $notesNotation->getNotation($range->lowest(), $this->notation),
            'highestForPrint' => $notesNotation->getNotation($range->highest(), $this->notation),
            'length'          => abs($this->nc->distanceWithOctave($range->lowest(), $range->highest())) - 1,
        ];
    }

	public function addTransposition(string $caption, string $cssClass, Transposition $transposition): void
	{
		$this->addVoice($caption, $cssClass, $transposition->range);
	}

	public function getChartHtml(): array
	{
		$min = $this->nc->lowestNote(array_column($this->voiceChart, 'lowest'));

		$nc  = $this->nc;

		array_walk($this->voiceChart, function(&$voice) use ($min, $nc) {
			$voice['offset'] = abs($nc->distanceWithOctave($min, $voice['lowest']));
		});

		return $this->voiceChart;
	}
}
