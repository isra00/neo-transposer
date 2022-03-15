<?php

namespace NeoTransposer\Model;

use NeoTransposer\AppAccess;
use NeoTransposer\Model\ChordPrinter\ChordPrinter;
use \NeoTransposer\NeoApp;
use \NeoTransposer\Persistence\SongPersistence;
use \NeoTransposer\Model\Song;

class TransposedSongFactory extends AppAccess
{
    /**
     * Factory
     *
     * @param string|int $idSong Song ID or slug.
     * @return TransposedSong                 The created object.
     */
    public function createTransposedSongFromSongId($idSong): TransposedSong
    {
        $songPersistence = new SongPersistence($this->app['db']);

        try
        {
            $song = $songPersistence->fetchSongByIdOrSlug($idSong);
        }
        catch (\Exception $e)
        {
            $this->app->abort(404, $e->getMessage());
        }

        return new TransposedSong($song, $this->app);
    }
}