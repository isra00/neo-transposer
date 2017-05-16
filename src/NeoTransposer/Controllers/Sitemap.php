<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Implements a XML Sitemap with login, book and song pages.
 */
class Sitemap
{
	/**
	 * Generates the Sitemap.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The Silex app
	 * @return string                     The rendered view
	 */
	public function get(\NeoTransposer\NeoApp $app)
	{
		$urls = array();

		$time = $app['neoconfig']['sitemap_lastmod'];

		$languages = array_keys($app['neoconfig']['languages']);

		foreach ($languages as $lang)
		{
			$urls[] = array(
				'loc' => $app->url('login', array('_locale' => $lang)),
				'priority' => 1,
				'changefreq' => 'daily',
				'lastmod' => $time
			);
		}

		$books = $app['books'];
		foreach ($books as $book)
		{
			$urls[] = array(
				'loc' => $app->url('book_' . $book['id_book'], array()),
				'priority' => 1,
				'changefreq' => 'daily',
				'lastmod' => $time
			);
		}

		$songs = $app['db']->fetchAll(
			'SELECT slug FROM song WHERE NOT id_song = 118 AND NOT id_song = 319'
		);

		foreach ($songs as $song)
		{
			$urls[] = array(
				'loc' => $app->url(
					'transpose_song', 
					array('id_song' => $song['slug'])
				),
				'priority' => '0.8',
				'changefreq' => 'weekly',
				'lastmod' => $time
			);
		}

		return new Response(
            $app['twig']->render('sitemap.twig', array('urls' => $urls)),
            200,
            ['Content-Type' => 'application/xml']
        );
	}
}
