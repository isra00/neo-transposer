<?php

namespace App\Domain;

final class SongsCollection
{
    /**
     * @param mixed[] $songs
     */
    public function __construct(
        /**
         * assoc array of id_song, slug, page, title
         * @todo Refactor this should be an array of Song objects
         */
        protected $songs
    )
    {
    }

    public function asArray(): array
    {
        return $this->songs;
    }
}