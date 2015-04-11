<?php

namespace NeoTransposer;

class SongForWizard
{
	/**
	 * Lyrics and chord placeholders, as fetched from config file.
	 * @var string
	 */
	protected $rawText;

	public function __construct($rawText)
	{
		$this->rawText = $rawText;
	}

	public function getHtmlTextWithChords($chords)
	{
		$placeholders = array();
		for ($i = 0; $i < count($chords); $i++)
		{
			$placeholders[] = "%$i";
		}

		$song = str_replace(' ', '&nbsp;', $this->rawText);
		$song = str_replace($placeholders, $chords, $song);
		$song = nl2br($song);

		return $song;
	}
}