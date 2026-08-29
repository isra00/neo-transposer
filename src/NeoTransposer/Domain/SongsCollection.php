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
         */
        protected $songs
    ) {
    }

    public function asArray(): array
    {
        return $this->songs;
    }
}
