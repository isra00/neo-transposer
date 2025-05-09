<?php

namespace NeoTransposer\Domain;

/**
 * Song text (with chords) that is shown in Wizard Empiric.
 */
final class SongTextForWizard
{
    public function __construct(
        /**
         * Lyrics and chord placeholders (%0, %1...), as fetched from config file.
         */
        protected string $rawText
    )
    {
    }

    /**
     * Place the given chords in a song text with placeholders
     *
     * @param array $chords Chords ready to be printed.
     * @return string            HTML song text with chords.
     *
     * @see    config.wizard.php
     */
    public function getHtmlTextWithChords(array $chords): string
    {
        $finalText = str_replace(' ', '&nbsp;', $this->rawText);
        foreach ($chords as $i => $chord) {
            $finalText = str_replace("%$i", (string) $chord, $finalText);
        }

        return nl2br($finalText, false);
    }
}
