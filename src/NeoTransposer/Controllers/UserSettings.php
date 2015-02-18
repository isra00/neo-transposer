<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\NotesCalculator;
use Symfony\Component\HttpFoundation\Request;

class UserSettings
{
	public function get(Request $request, \NeoTransposer\NeoApp $app)
	{
		$nc = new NotesCalculator;

		//Probably the user will want the book in their own language.
		$default_book_locale = !empty($app['user']->id_book)
			? $app['books'][$app['user']->id_book]['locale']
			: $app['locale'];

		return $app->render('user_settings.tpl', array(
			'page_title'			=> $app->trans('Settings'),
			'default_book_locale'	=> $default_book_locale,
			'scale'					=> $nc->numbered_scale,
			'accoustic_scale'		=> $nc->accoustic_scale,
			'current_notation'		=> $app['neoconfig']['languages'][$app['locale']]['notation'],
			'redirect'				=> $request->get('redirect'),
		));
	}
}