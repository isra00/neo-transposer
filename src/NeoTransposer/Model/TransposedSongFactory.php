<?php

namespace NeoTransposer\Model;

use NeoTransposer\AppAccess;
use NeoTransposer\Model\ChordPrinter\ChordPrinter;
use NeoTransposer\NeoApp;
use NeoTransposer\Persistence\SongPersistence;
use NeoTransposer\Model\Song;

/**
 * Esto podría ser, en vez de una clase con un sólo método, un servicio Silex.
 */
class TransposedSongFactory extends AppAccess
{
    /**
     * Factory
     *
     * @param string|int $idSong Song ID or slug.
     * @return TransposedSong                 The created object.
     *
     * @refactor Esto es claramente overdesign. TransposedSong::fromId($db, $id) es mejor.
     *           No haría falta testarla (solo tendría 1 línea). Las excepciones se atrapan en los
     *           clientes, ya que el controller TransposeSong manejaría el "song not found" de una
     *           manera (404) distinta a la de testAllTranspositions o Wizard (500).
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
            //Que una clase de dominio devuelva 404 es mezclar capas (ver @refactor arriba)
            $this->app->abort(404, $e->getMessage());
        }

        return new TransposedSong($song, $this->app);
    }
}