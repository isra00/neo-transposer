<?php

namespace NeoTransposer\Domain;

class SongsCollection
{
    /**
     * assoc array of id_song, slug, page, title
     * @var array
     */
    protected $songs;

    public function __construct($songs)
    {
        $this->songs = $songs;
    }

    public function asArray(): array
    {
        return $this->songs;
    }
}