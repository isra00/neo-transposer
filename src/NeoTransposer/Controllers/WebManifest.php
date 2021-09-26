<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebManifest
{
	public function get(\NeoTransposer\NeoApp $app, Request $req)
	{
		$startUrl = '/';

		if ($req->get('loggedIn'))
		{
			$startUrl = $app->path('pwa', ['_locale' => $app['locale'], 'id_user' => $app['neouser']->id_user]);
		}

		$json = [
			'name' 			=> 'Neo-Transposer',
			'short_name' 	=> 'Neo-Transposer',
			'description' 	=> $app->trans('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!'),
			'categories'	=> 'utilities',
			'background_color' => '#D32F2F',
			'theme_color' 	=> '#D32F2F',
			'display' 		=> 'standalone',
			'lang'			=> $app['locale'],
			'start_url' 	=> $startUrl,
			'icons' => [
				[
					'src' 	=> '/static/img/source/logo-red-maskable.svg',
					'sizes'	=> '512x512',
					'type' 	=> 'image/svg+xml',
					'purpose' => 'maskable'
				],
				[
					'src' 	=> '/static/img/source/logo-red.svg',
					'sizes'	=> '512x512',
					'type' 	=> 'image/svg+xml',
					'purpose' => 'any'
				],
				[
					'src' 	=> '/static/img/icons/maskable-192.png',
					'sizes'	=> '192x192',
					'type' 	=> 'image/png',
					'purpose' => 'any'
				],
				[
					'src' 	=> '/static/img/icons/maskable-512.png',
					'sizes'	=> '512x512',
					'type' 	=> 'image/png',
					'purpose' => 'any'
				],
				[
					'src' 	=> '/static/img/icons/maskable-180.png',
					'sizes'	=> '180x180',
					'type' 	=> 'image/png',
					'purpose' => 'any'
				]
			]
		];

		$response = new Response();
		$response->setContent(json_encode($json));
		$response->headers->set('Content-Type', 'application/manifest+json');
		/*$response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->headers->set('Pragma', 'no-cache');
		$response->headers->set('Expires', '0');*/

		return $response;
	}
}
