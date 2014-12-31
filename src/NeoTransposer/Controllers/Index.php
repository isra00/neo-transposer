<?php

namespace NeoTransposer\Controllers;

class Login
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		if ($app['user']->id_book)
		{
			return $app->redirect($app['url_generator']->generate(
				'book', 
				array('id_book' => $app['user']->id_book)
			));
		}

		return $app->redirect($app['url_generator']->generate('login'));
	}
}
