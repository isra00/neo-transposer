<?php

namespace NeoTransposer;

class SongForWizard
{
	/**
	 * Lyrics and chord placeholders, as fetched from config file.
	 * @var string
	 */
	protected $rawText;

	protected function __construct($rawText)
	{
		$this->rawText = $rawText;
	}

	public static function createSongForWizard(\NeoTransposer\NeoApp $app, $songIndex)
	{
		return new SongForWizard($app['neoconfig']['voice_wizard'][$app['locale']][$songIndex]['song_contents']);
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