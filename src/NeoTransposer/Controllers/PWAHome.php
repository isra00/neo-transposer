<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Offline web app home page
 */
class PWAHome
{
	public function get(\NeoTransposer\NeoApp $app, Request $req)
	{
		return $app->render('pwa_home.twig', [
			
		]);
	}
}