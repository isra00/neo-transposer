<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Response;

final class WebManifest
{
	public function get(\NeoTransposer\NeoApp $app)
	{

		$json = [
			"name" 			=> $app->trans('Transpose the songs of the Neocatechumenal Way · Neo-Transposer'),
			"short_name" 	=> "Neo Transposer",
			"description" 	=> $app->trans('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!'),
			"categories"	=> "utilities",
			"background_color" => "#D32F2F",
			"theme_color" 	=> "#D32F2F",
			"display" 		=> "standalone",
			"lang"			=> $app['locale'],
			"start_url" 	=> "/",
			"icons" => [
				[
					"src" 	=> "/static/img/icon/source/logo-red-maskable.svg",
					"sizes"	=> "512x512",
					"type" 	=> "image/svg+xml",
					"purpose" => "any maskable"
				],
				[
					"src" 	=> "/static/img/icon-192x192.png",
					"sizes"	=> "192x192",
					"type" 	=> "image/png",
					"purpose" => "any maskable"
				],
				[
					"src" 	=> "/static/img/icon-512x512.png",
					"sizes"	=> "512x512",
					"type" 	=> "image/png",
					"purpose" => "any maskable"
				],
				[
					"src" 	=> "/static/img/apple-touch-icon.png",
					"sizes"	=> "180x180",
					"type" 	=> "image/png",
					"purpose" => "any maskable"
				]
			]
		];

		$response = new Response();
		$response->setContent(json_encode($json));
		$response->headers->set('Content-Type', 'application/manifest+json');
		return $response;
	}
}
