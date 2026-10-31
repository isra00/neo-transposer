<?php

namespace App\Console\Commands;

use App\Support\OfficialWebsiteSongMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Warms the cache of the edit-song page's media embeds for a whole songbook, so reviewing it
 * song by song does not wait for the publisher's site on every page.
 */
class PrefetchSongMediaCommand extends Command
{
    protected $signature = 'app:prefetch-song-media
        {id_book : Songbook whose songs\' urls are scraped}
        {--force : Scrape again the urls already cached}';

    protected $description = 'Pre-fetch the edit-song page media (songbook page image, recordings) of all songs of a songbook';

    public function handle(): int
    {
        $songs = DB::table('song')
            ->where('id_book', (int) $this->argument('id_book'))
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('page')
            ->orderBy('title')
            ->get(['id_song', 'title', 'url']);

        if ($songs->isEmpty()) {
            $this->error('No songs with URLs in that songbook.');

            return self::FAILURE;
        }

        $failed = [];

        $this->withProgressBar($songs, function ($song) use (&$failed) {
            if ($this->option('force')) {
                OfficialWebsiteSongMedia::forget($song->url);
            }

            $media = OfficialWebsiteSongMedia::forUrl($song->url);

            if ($media->error !== null) {
                $failed[] = "#{$song->id_song} \"{$song->title}\": {$media->error} — {$song->url}";
            }
        });

        $this->newLine(2);

        $this->info(($songs->count() - count($failed)) . " of {$songs->count()} song pages cached.");

        foreach ($failed as $line) {
            $this->warn("  {$line}");
        }

        return self::SUCCESS;
    }
}
