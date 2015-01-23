<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\AutomaticTransposer;
use \NeoTransposer\TranspositionChart;
use \NeoTransposer\NotesCalculator;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

			$app['user']->id_book = intval($request->get('book'));

			// Auto-select chord printer based on book.
			if (!$request->get('chord_printer'))
			{
				$app['user']->chord_printer = $app['books'][$app['user']->id_book]['chord_printer'];
			}
		}

		if ($request->get('chord_printer'))
		{
			if (false === array_search($request->get('chord_printer'), array_keys($app['chord_printers.list'])))
			{
				throw new BadRequestHttpException('Invalid request: the specified chord notation does not exist');
			}

			$app['user']->chord_printer = $request->get('chord_printer');
		}

		if ($request->get('lowest_note'))
		{
			$app['user']->lowest_note = $request->get('lowest_note');
		}

		if ($request->get('highest_note'))
		{
			$app['user']->highest_note = $request->get('highest_note');
		}

		$app['user']->persist($app['db']);

		return $app->redirect($request->get('redirect')
			? $request->get('redirect')
			: $app['url_generator']->generate('book_' . $app['user']->id_book)
		);
	}
}