<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements an XML Sitemap with login, book and song pages.
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
				'priority' => 1,
				'changefreq' => 'weekly',
            ];

			$urls[] = [
				'loc' => $app->url('people-compatible-info', ['_locale' => $lang]),
				'priority' => 1,
				'changefreq' => 'monthly',
            ];

			$urls[] = [
				'loc' => $app->url('manifesto', ['_locale' => 'es']),
				'priority' => 1,
				'changefreq' => 'monthly',
            ];
		}

		$books = $app['books'];
		foreach ($books as $book)
		{
			$urls[] = [
				'loc' => $app->url('book_' . $book['id_book'], []),
				'priority' => 1,
				'changefreq' => 'daily',
            ];
		}

		$songs = $app['db']->fetchAll(
			'SELECT slug FROM song WHERE NOT id_song = 118 AND NOT id_song = 319'
		);

		foreach ($songs as $song)
		{
			$urls[] = [
				'loc' => $app->url(
					'transpose_song',
					['id_song' => $song['slug']]
				),
				'priority' => '0.8',
				'changefreq' => 'weekly',
            ];
		}

		return new Response(
            $app['twig']->render('sitemap.twig', ['urls' => $urls]),
            200,
            ['Content-Type' => 'application/xml']
        );
	}
}
