<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use NeoTransposer\Domain\Repository\SongChordRepository;

class CheckOrphanChords implements AdminTask
{
    public function __construct(protected SongChordRepository $songChordRepository)
    {
    }
    
	public function run(): string
	{
        $orphanIdSongs = $this->songChordRepository->readSongsWithOrphanChords();

		return (empty($orphanIdSongs))
			? 'Good! No orphan chord detected.'
			: count($orphanIdSongs) . ' orphan id_song detected! Remove them with'
				. "\nDELETE FROM song_chord WHERE id_song IN (" . implode(', ', $orphanIdSongs) . ')';
	}
}