<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

class UserVoice
{
	public function get(Request $request, \NeoTransposer\NeoApp $app)
	{
		$nc = new \NeoTransposer\NotesCalculator;

		$redirect = $request->get('redirect');

		//First usage: user is redirected to the book in their language
		if (!$redirect)
		{
			foreach ($app['books'] as $book)
			{
				if ($book['locale'] == $app['locale'])
				{
					$redirect = $app['url_generator']->generate('book_' . $book['id_book']);
				}
			}
		}

		return $app->render('user_voice.tpl', array(
			'page_title'			=> $app->trans('Your voice'),
			'scale'					=> $nc->numbered_scale,
			'accoustic_scale'		=> $nc->accoustic_scale,
			'current_notation'		=> $app['neoconfig']['languages'][$app['locale']]['notation'],
			'redirect'				=> $redirect
		));
	}
}