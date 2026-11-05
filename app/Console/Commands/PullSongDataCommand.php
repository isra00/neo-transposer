<?php

namespace App\Console\Commands;

use App\Support\SongData;
use Illuminate\Console\Command;

class PullSongDataCommand extends Command
{
    protected $signature = 'app:pull-song-data
        {dump : Data-only dump of the production song tables}
        {migrations : The migrations production has run, one per line}';

    protected $description = 'Bring the song data edited in production since song_data.sql into the local DB and song_data.sql';

    public function handle(): int
    {
        // song_data.sql is ahead of a production that has not run every migration: what
        // those migrations change would look like production edits, and be undone here.
        $ran = array_filter(array_map('trim', file($this->argument('migrations'))));
        $pending = array_diff(array_map(fn ($file) => basename($file, '.php'), glob(database_path('migrations/*.php'))), $ran);
        if ($pending) {
            $this->error('Production has not run these migrations yet. Deploy them before pulling:');
            foreach ($pending as $migration) {
                $this->line("  $migration");
            }

            return self::FAILURE;
        }

        $committed = SongData::loadSnapshot('committed', base_path(SongData::FILE));
        $production = SongData::loadSnapshot('production', $this->argument('dump'));

        try {
            $changes = SongData::changes(SongData::read($committed), SongData::read($production));
            if (!$changes) {
                $this->info('Production has no song data edits since song_data.sql.');

                return self::SUCCESS;
            }

            foreach ($changes as [$operation, $table, $key, $data]) {
                $this->line("  $operation $table $key " . json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            // Into song_data.sql always: it then records what production has.
            SongData::apply($changes, $committed);
            SongData::export(base_path(SongData::FILE), $committed);

            // Into the local DB, unless the same values were edited here too.
            $conflicts = SongData::apply($changes);
        } finally {
            SongData::dropSnapshot($committed);
            SongData::dropSnapshot($production);
        }

        if ($conflicts) {
            $this->warn('Edited both in production and locally, so left as they are locally. Decide on each one:');
            foreach ($conflicts as $conflict) {
                $this->line("  $conflict");
            }
            $this->warn('The local value is the one the next app:song-data-migration will take to production.');
        }
        $this->info('Pulled ' . (count($changes) - count($conflicts)) . ' changes. song_data.sql updated: commit it.');

        return self::SUCCESS;
    }
}
