<?php

namespace NeoTransposer\Domain;

final class SongsCollection
{
    /**
     * @param  array  $songs
     */
    public function __construct(
        /**
         * assoc array of stdClass objects
         *
         * @todo Refactor: this should be an array of Song objects, instead of raw stdClass objects from DB query.
         */
        protected $songs
    ) {
    }

    public function asArray(): array
    {
        return $this->songs;
    }
}
