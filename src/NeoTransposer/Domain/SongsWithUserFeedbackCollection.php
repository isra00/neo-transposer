<?php

namespace NeoTransposer\Domain;

//Idealmente esto sería un iterable, blablabla
class SongsWithUserFeedbackCollection
{
    /**
     * assoc array of id_song, slug, page, title, worked
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