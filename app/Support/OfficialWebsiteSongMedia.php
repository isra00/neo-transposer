<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * A song's url column points at the publisher's page for that song, which carries the
 * songbook page image and a recording. The edit-song page embeds them so the whole song
 * can be read and listened to without leaving the form, so we scrape the page for them.
 *
 * Publishers differ: chantscnc.fr links the songbook page image as a PDF,
 * cantos.cnc.madrid puts it in <img> tags, one per songbook page, and
 * cantineocatecumenale.it only as the page's og:image (its <img> tags are app store badges
 * and banners). All are collected, and the view falls back to the images when there is no PDF.
 *
 * The scrape is cached: while editing a songbook the same page is reloaded often, and
 * the publisher's site is slow enough for a round trip per reload to be noticeable.
 */
final class OfficialWebsiteSongMedia
{
    private const CACHE_TTL_SECONDS = 86400;

    /**
     * Page furniture that is an <img> but never a songbook page image. Matched against the
     * whole URL.
     */
    private const NOT_A_SONGBOOK_PAGE_IMAGE = ['favicon', 'logo', 'avatar', 'icon', 'sprite', 'banner', 'gravatar'];

    /**
     * @param  string[]  $audioUrls  Recordings, in page order: some songs have several
     *                               melodies, one recording each.
     * @param  string[]  $imageUrls  Songbook page images, in the order the publisher's page
     *                               lists them.
     */
    private function __construct(
        public readonly ?string $pdfUrl,
        public readonly array $audioUrls,
        public readonly array $imageUrls,
        public readonly ?string $error
    ) {
    }

    public static function forUrl(?string $songUrl): self
    {
        if (empty($songUrl)) {
            return new self(null, [], [], null);
        }

        $cached = self::cache()->get(self::cacheKey($songUrl));

        if (is_array($cached)) {
            return new self($cached['pdf'], $cached['audio'], $cached['images'], null);
        }

        try {
            $response = Http::timeout(10)->connectTimeout(5)->get($songUrl);
        } catch (\Throwable $e) {
            return new self(null, [], [], $e->getMessage());
        }

        if (!$response->successful()) {
            return new self(null, [], [], 'HTTP ' . $response->status() . ' fetching the song page');
        }

        $html = $response->body();

        $media = [
            'pdf'    => self::linksWithExtension($html, $songUrl, ['pdf'])[0] ?? null,
            'audio'  => self::linksWithExtension($html, $songUrl, ['mp3', 'm4a', 'ogg', 'wav']),
            'images' => self::songbookPageImages($html, $songUrl),
        ];

        self::cache()->put(self::cacheKey($songUrl), $media, self::CACHE_TTL_SECONDS);

        return new self($media['pdf'], $media['audio'], $media['images'], null);
    }

    public static function forget(?string $songUrl): void
    {
        if (!empty($songUrl)) {
            self::cache()->forget(self::cacheKey($songUrl));
        }
    }

    private static function cache(): CacheRepository
    {
        // Force file-based cache backend, to prevent Laravel's default (DB).
        return Cache::store('file');
    }

    private static function cacheKey(string $songUrl): string
    {
        return 'official_website_song_media_v3:' . md5($songUrl);
    }

    /**
     * Distinct links, in page order. The query string is dropped: players link the same
     * file once as a download (href) and again with a cache buster (src="...mp3?_=1").
     *
     * @param  string[]  $extensions
     * @return string[]
     */
    private static function linksWithExtension(string $html, string $pageUrl, array $extensions): array
    {
        $pattern = '/(?:href|src)\s*=\s*["\']([^"\']+\.(?:' . implode('|', $extensions) . '))(?:\?[^"\']*)?["\']/i';

        preg_match_all($pattern, $html, $matches);

        return array_values(array_unique(array_map(
            fn ($url) => self::absolutize(html_entity_decode($url), $pageUrl),
            $matches[1]
        )));
    }

    /**
     * Only <img> sources: the page furniture that shares their extensions (favicons and
     * the like) is linked with <link href>, so restricting to <img> keeps them out.
     *
     * @return string[]
     */
    private static function songbookPageImages(string $html, string $pageUrl): array
    {
        if (str_ends_with(parse_url($pageUrl, PHP_URL_HOST) ?? '', 'cantineocatecumenale.it')) {
            return preg_match('/<meta\b[^>]*?\bproperty\s*=\s*["\']og:image["\'][^>]*?\bcontent\s*=\s*["\']([^"\']+)["\']/i', $html, $match)
                ? [self::absolutize(html_entity_decode($match[1]), $pageUrl)]
                : [];
        }

        preg_match_all('/<img\b[^>]*?\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $matches);

        $images = [];

        foreach ($matches[1] as $src) {
            $src = html_entity_decode($src);

            if (str_starts_with($src, 'data:')) {
                continue;
            }

            if (!preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $src)) {
                continue;
            }

            foreach (self::NOT_A_SONGBOOK_PAGE_IMAGE as $furniture) {
                if (stripos($src, $furniture) !== false) {
                    continue 2;
                }
            }

            $images[] = self::absolutize($src, $pageUrl);
        }

        return array_values(array_unique($images));
    }

    private static function absolutize(string $url, string $pageUrl): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $base = parse_url($pageUrl);
        $origin = $base['scheme'] . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');

        if (str_starts_with($url, '//')) {
            return $base['scheme'] . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }

        return $origin . rtrim(dirname($base['path'] ?? '/'), '/') . '/' . $url;
    }
}
