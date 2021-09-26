<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceWorker
{
	public function get(\NeoTransposer\NeoApp $app, Request $req)
	{
		$response = new Response();
		$response->setContent($app->render(
			'service_worker.js.twig',
			[
				//'sw_version' => $app['debug'] ? rand() : 4,
				'sw_version' => 13,
				'userRange' => $app['neouser']->range
			],
			false
		));
		$response->headers->set('Content-Type', 'application/javascript');
		return $response;
	}
}