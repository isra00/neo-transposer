<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Infrastructure\UserRepositoryMysql;
use NeoTransposer\Model\NotesRange;
use NeoTransposer\Model\User;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Sets the user data and redirect. There is no response body.
 *
 * @todo Rename to UpdateUser
 */
class SetUserData
{
	public function get(Request $request, \NeoTransposer\NeoApp $app): RedirectResponse
	{
		if ($request->get('book'))
		{
			if (!in_array($request->get('book'), array_keys($app['books'])))
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
            /** @todo En PHP 8, str_contains() */
			if (strpos($request->get('highest_note'), '1'))
			{
				return $app->redirect($app->path(
					'user_voice', 
					array('bad_voice_range'=>'1')
				));
			}

			$app['neouser']->range->highest = $request->get('highest_note');
		}

		$app['neouser']->persistWithVoiceChange(
			$app['db'], 
			empty($request->get('unhappy_choose_std')) ? User::METHOD_MANUAL : User::METHOD_UNHAPPY
		);

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
