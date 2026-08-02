<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class TestSongUrlsCommand extends Command
{
    private const CONCURRENT_REQUESTS = 5;

    /**
     * Statuses meaning "we were not allowed to look", not "the link is dead".
     * Typically a WAF or rate limiter reacting to the source IP, which on CI is
     * a datacenter range. They say nothing about whether the song URL works, so
     * reporting them as broken links is a false positive.
     */
    private const BLOCKED_STATUSES = [401, 403, 405, 429, 503];

    protected $signature = 'app:test-song-urls';

    protected $description = 'Report song URLs in the database that look unreachable (never fails the build)';

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

        $broken = [];
        $blocked = [];

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
                    $broken[] = "#{$song->id_song} \"{$song->title}\": {$response->getMessage()} — {$song->url}";
                    $this->output->write('<fg=red>F</>');
                    continue;
                }

                $status = $response->status();

                if ($response->successful()) {
                    $this->output->write('.');
                } elseif (in_array($status, self::BLOCKED_STATUSES, true)) {
                    $blocked[] = [
                        'host'   => parse_url($song->url, PHP_URL_HOST) ?: '(unknown host)',
                        'status' => $status,
                    ];
                    $this->output->write('<fg=yellow>B</>');
                } else {
                    $broken[] = "#{$song->id_song} \"{$song->title}\": HTTP {$status} — {$song->url}";
                    $this->output->write('<fg=red>F</>');
                }
            }

            usleep(200_000);
        }

        $this->newLine(2);

        $ok = $songs->count() - count($broken) - count($blocked);
        $this->info("Checked {$songs->count()}: {$ok} OK, " . count($broken) . ' unreachable, ' . count($blocked) . ' not verified.');

        if ($blocked) {
            $this->reportBlocked($blocked);
        }

        if ($broken) {
            $this->reportBroken($broken);
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{host: string, status: int}> $blocked
     */
    private function reportBlocked(array $blocked): void
    {
        $byHost = [];
        foreach ($blocked as $entry) {
            $key = "{$entry['host']} (HTTP {$entry['status']})";
            $byHost[$key] = ($byHost[$key] ?? 0) + 1;
        }

        $this->newLine();
        $this->warn(count($blocked) . ' URL(s) could not be verified — the checker was blocked, which is not evidence the links are broken:');
        foreach ($byHost as $key => $count) {
            $this->line("  {$count} × {$key}");
        }

        $summary = [];
        foreach ($byHost as $key => $count) {
            $summary[] = "{$count} × {$key}";
        }
        $this->annotate('notice', 'Song URLs not verified', implode(', ', $summary));
    }

    /**
     * @param array<int, string> $broken
     */
    private function reportBroken(array $broken): void
    {
        $this->newLine();
        $this->warn(count($broken) . ' song URL(s) look broken:');
        foreach ($broken as $line) {
            $this->line("  {$line}");
        }

        $this->annotate(
            'warning',
            'Broken song URLs',
            count($broken) . ' song URL(s) look broken — see the job log for the full list.'
        );
    }

    /**
     * Surface a summary in the GitHub Actions UI. One annotation per category:
     * GitHub renders only the first 10 of each type per step, so a per-URL
     * annotation would silently truncate.
     */
    private function annotate(string $level, string $title, string $message): void
    {
        if (!getenv('GITHUB_ACTIONS')) {
            return;
        }

        $escape = static fn (string $value): string => str_replace(
            ['%', "\r", "\n"],
            ['%25', '%0D', '%0A'],
            $value
        );

        $title = $escape($title);
        $message = $escape($message);

        $this->line("::{$level} title={$title}::{$message}");
    }
}
