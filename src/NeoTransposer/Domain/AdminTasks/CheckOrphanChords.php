<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use NeoTransposer\Domain\Repository\SongChordRepository;

class CheckOrphanChords implements AdminTask
{
    protected $songChordRepository;

    public function __construct(SongChordRepository $songChordRepository)
    {
        $this->songChordRepository = $songChordRepository;
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
