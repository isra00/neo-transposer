<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class TestSongUrlsCommand extends Command
{
    private const CONCURRENT_REQUESTS = 5;

    protected $signature = 'app:test-song-urls';

    protected $description = 'Verify that all song URLs in the database return HTTP 200';

    public function handle(): int
    {
        $songs = DB::table('song')
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->select('id_song', 'title', 'url')
            ->get();

        if ($songs->isEmpty()) {
            $this->error('No songs with URLs found in the database.');
            return self::FAILURE;
        }

        $this->info("Checking {$songs->count()} song URLs (" . self::CONCURRENT_REQUESTS . " concurrent)...");

        $failures = [];

        foreach ($songs->chunk(self::CONCURRENT_REQUESTS) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                foreach ($chunk as $song) {
                    $pool->as($song->id_song)
                        ->timeout(10)
                        ->connectTimeout(5)
                        ->withOptions(['allow_redirects' => true])
                        ->head($song->url);
                }
            });

            foreach ($chunk as $song) {
                $response = $responses[$song->id_song];

                if ($response instanceof \Throwable) {
                    $failures[] = "#{$song->id_song} \"{$song->title}\": {$response->getMessage()} — {$song->url}";
                    $this->output->write('<fg=red>F</>');
                } elseif (!$response->successful()) {
                    $failures[] = "#{$song->id_song} \"{$song->title}\": HTTP {$response->status()} — {$song->url}";
                    $this->output->write('<fg=red>F</>');
                } else {
                    $this->output->write('.');
                }
            }

            usleep(200_000);
        }

        $this->newLine(2);

        if (empty($failures)) {
            $this->info("OK ({$songs->count()} URLs)");
            return self::SUCCESS;
        }

        $this->error(count($failures) . " song URL(s) failed:");
        foreach ($failures as $failure) {
            $this->line("  $failure");
        }

        return self::FAILURE;
    }
}
