<?php

namespace NeoTransposer\Domain;

//Idealmente esto sería un iterable, blablabla
class SongsWithUserFeedbackCollection
{
    /**
     * assoc array of song.*, transposition_feedback.worked, transposition_feedback.transposition transposition_which_worked, book.chord_printer, book.locale, id_book
     * @var array
     */
    protected $songsWithUserFeedback;

    public function __construct($songsWithUserFeedback)
    {
        $this->songsWithUserFeedback = $songsWithUserFeedback;
    }

    public function asArray(): array
    {
        return $this->songsWithUserFeedback;
    }
}