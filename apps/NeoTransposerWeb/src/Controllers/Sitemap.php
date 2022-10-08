<?php

namespace NeoTransposerWeb\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposerWeb\NeoApp;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements an XML Sitemap with login, static pages, book and song pages.
 */
class Sitemap
{
    /**
     * Generates the Sitemap.
     *
     * @param NeoApp $app The Silex app
     *
     * @return Response The rendered view
     */
	public function get(NeoApp $app): Response
    {
		$urls = [];

		$languages = array_keys($app['neoconfig']['languages']);

		foreach ($languages as $lang)
		{
			$urls[] = [
				'loc' => $app->url('login', ['_locale' => $lang]),
            ];

			$urls[] = [
				'loc' => $app->url('people-compatible-info', ['_locale' => $lang]),
            ];

			$urls[] = [
				'loc' => $app->url('manifesto', ['_locale' => 'es']),
            ];
		}

		$books = $app[BookRepository::class]->readAllBooks();
		foreach ($books as $book)
		{
			$urls[] = [
				'loc' => $app->url('book_' . $book->idBook(), []),
            ];
		}

        $songRepository = $app[SongRepository::class];
        $songs = $songRepository->readAllSongs();

		foreach ($songs as $song)
		{
			$urls[] = [
				'loc' => $app->url(
					'transpose_song',
					['id_song' => $song['slug']]
				),
            ];
		}

		return new Response(
            $app['twig']->render('sitemap.twig', ['urls' => $urls]),
            200,
            ['Content-Type' => 'application/xml']
        );
	}
}
