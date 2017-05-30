<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Sets the user data and redirect. There is no response body.
 */
class SetUserData
{
	public function get(Request $request, \NeoTransposer\NeoApp $app)
	{
		if ($request->get('book'))
		{
			if (false === array_search($request->get('book'), array_keys($app['books'])))
			{
				throw new BadRequestHttpException('Invalid request: the specified book does not exist');
			}

			$app['neouser']->id_book = intval($request->get('book'));
		}

		if (empty($app['neouser']->range) && ($request->get('lowest_note') || $request->get('highest_note')))
		{
			$app['neouser']->range = new NotesRange;
		}

		if ($request->get('lowest_note'))
		{
			$app['neouser']->range->lowest = $request->get('lowest_note');
		}

		if ($request->get('highest_note'))
		{
			if (strpos($request->get('highest_note'), '1'))
			{
				return $app->redirect($app->path(
					'user_voice', 
					array('bad_voice_range'=>'1')
				));
			}

			$app['neouser']->range->highest = $request->get('highest_note');
		}

		$app['neouser']->persist($app['db'], $request->getClientIp());

		if ($request->get('unhappy_choose_std'))
		{
			$unhappy = new \NeoTransposer\Model\UnhappyUser($app);

			try {
				$unhappy->chooseStandard($app['neouser'], $request->get('unhappy_choose_std'));
			} 
			catch (\UnexpectedValueException $e)
			{
				$app->abort(400, 'Bad value for URL parameter unhappy_choose_std');
			}
		}

		return $app->redirect($request->get('redirect')
			? $request->get('redirect')
			: $app->path('book_' . $app['neouser']->id_book)
		);
	}
}
