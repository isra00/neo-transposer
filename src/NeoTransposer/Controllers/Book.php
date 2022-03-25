<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Application\ListSongsWithUserFeedback;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Book page: show list of songs belonging to a given book.
 */
class Book
{
	public function get(Request $req, \NeoTransposer\NeoApp $app, $id_book)
	{
		if (empty($app['books'][$id_book]))
		{
			$app->abort(404, "Book $id_book does not exist.");
		}

        $useCaseListSongsWithUserFeedback = $app[ListSongsWithUserFeedback::class];
        $songs = $useCaseListSongsWithUserFeedback->ListSongsWithUserFeedbackAsArray((int) $id_book, $app['neouser']->id_user);

		$template = 'book.twig';

		$showEncourageFeedback = !empty($app['neouser']->range->lowest) && ($app['neouser']->feedbacksReported < 2 || ($app['neouser']->feedbacksReported == 2 && $app['neouser']->firstTime));

		//If first time or user has reported < 2 fb, show encourage fb banners
		if ($showEncourageFeedback)
		{
			$yourVoice = $app['neouser']->getVoiceAsString(
				$app['translator'],
				$app['neoconfig']['languages'][$app['locale']]['notation']
			);

			$userPersistence = new \NeoTransposer\Persistence\UserPersistence($app['db']);
			$userPerformance = $userPersistence->fetchUserPerformance($app['neouser'])['performance'];

			$template = 'book_encourage_feedback.twig';
		}

		$app['locale'] = $app['books'][$id_book]['locale'];
		$app['translator']->setLocale($app['locale']);

		$unhappy = new \NeoTransposer\Model\UnhappyUser($app);

		$response = new Response($app->render($template, array(
			'page_title'	 		=> $app->trans('Songs of the Neocatechumenal Way in %lang%', array('%lang%' => $app['books'][$id_book]['lang_name'])),
			'current_book'	 		=> $app['books'][$id_book],
			'header_link'	 		=> $app->path('book_' . $app['books'][$id_book]['id_book']),
			'songs'			 		=> $songs,
			'show_unhappy_warning'	=> $unhappy->isUnhappyNoAction($app['neouser']),
			'meta_description'		=> $app->trans(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				array('%lang%' => $app['books'][$id_book]['lang_name'])
			),
			'your_voice'			=> $yourVoice ?? null,
			'user_performance'		=> $userPerformance ?? null,
			'show_encourage_fb'		=> $showEncourageFeedback
		)), 200);

		if ($showEncourageFeedback)
		{
			$response->headers->add([
				'Cache-Control' => 'private, must-revalidate, max-age=0',
				'Pragma'		=> 'no-cache',
				'Expires'		=> 'Sat, 26 Jul 1997 05:00:00 GMT'
			]);
		}

		return $response;
	}
}
