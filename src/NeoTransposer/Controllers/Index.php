<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

class Index
{
	/**
	 * Controller for the / route. If logged in, redirect to the book. If not,
	 * redirect to login in the language of the browser (Accept-Language header).
	 * 
	 * @param  Request               $req The HTTP request.
	 * @param  \NeoTransposer\NeoApp $app The NeoApp.
	 * @return RedirectResponse      A redirection to the proper page.
	 */
	public function get(Request $req, \NeoTransposer\NeoApp $app)
	{
		if ($app['user']->id_book)
		{
			return $app->redirect($app['url_generator']->generate(
				'book_' . $app['user']->id_book
			));
		}

		return $app->redirect($app['url_generator']->generate(
			'login', 
			array('_locale' => $app['locale'])
		));
	}
}
