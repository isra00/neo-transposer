<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\NotesNotation;

/**
 * Page for the user to set his/her voice range, or to go to the Wizard.
 */
class UserVoice
{
	public function get(Request $request, \NeoTransposer\NeoApp $app): string
	{
		if ($request->get('bad_voice_range'))
		{
			$app->addNotification('error', $app->trans('Are you sure that is your real voice range? If you don\'t know, you can use the assistant to measure it.'));
		}

		$nc = new \NeoTransposer\Model\NotesCalculator;

		$redirect = $request->get('redirect');

		//First usage: user is redirected to the book in their language
		if (!$redirect)
		{
			foreach ($app['books'] as $book)
			{
				if ($book['locale'] == $app['locale'])
				{
					$redirect = $app->path('book_' . $book['id_book']);
					$app['neouser']->id_book = $book['id_book'];
					$app['neouser']->persist($app['db'], $request->getClientIp());
				}
			}
		}

		$accousticScaleNice = [];
        $notesNotation = new NotesNotation;
		foreach ($nc->accoustic_scale as $note)
		{
			$accousticScaleNice[] = $notesNotation->getNotation(
				$note, 
				$app['neoconfig']['languages'][$app['locale']]['notation']
			);
		}

		return $app->render('user_voice.twig', array(
			'page_title'			=> $app->trans('Your voice'),
			'scale'					=> $nc->numbered_scale,
			'accoustic_scale'		=> $nc->accoustic_scale,
			'accoustic_scale_nice'	=> $accousticScaleNice,
			'redirect'				=> $redirect
		));
	}
}
