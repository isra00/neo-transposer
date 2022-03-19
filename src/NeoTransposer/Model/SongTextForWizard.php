<?php

namespace NeoTransposer\Model;

/**
 * Song text (with chords) that is shown in Wizard Empiric.
 */
class SongTextForWizard
{
	/**
	 * Lyrics and chord placeholders (%0, %1...), as fetched from config file.
	 * 
	 * @var string
	 */
	protected $rawText;

	public function __construct($rawText)
	{
		$this->rawText = $rawText;
	}

	/**
	 * Place the given chords in a song text with placeholders
	 * 
	 * @param  array	$chords Chords ready to be printed.
	 * @return string         	HTML song text with chords.
	 * 
	 * @see    config.wizard.php
     *
     * @todo Refactor. Este bucle generando placeholders es absurdo, se puede ir sustituyendo por la marcha.
	 */
	public function getHtmlTextWithChords(array $chords): string
    {
		$finalText = str_replace(' ', '&nbsp;', $this->rawText);
        foreach ($chords as $i=>$chord)
        {
            $finalText = str_replace("%$i", strval($chord), $finalText);
        }

		$finalText = nl2br($finalText);

		return $finalText;
	}
}
