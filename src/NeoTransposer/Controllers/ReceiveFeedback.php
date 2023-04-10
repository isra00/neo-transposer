<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Service\FeedbackRecorder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AJAX Controller to receive/record user feedback. This URL has NO CONTENT.
 */
final class ReceiveFeedback
{
	public function post(Request $req, \NeoTransposer\NeoApp $app): Response
	{
		//This usually happens when the sessions times out (=> HTTP status 408).
		if (!$app['neouser']->isLoggedIn())
		{
            //The JSON body is superfluous since JS reads the status code only.
			return ($req->isXmlHttpRequest())
				? $app->json([], 408)
				: $app->redirect($req->server->get('HTTP_REFERER'));
		}

		if (empty($req->get('id_song')) || null === $req->get('worked'))
		{
			return $app->json(['error' => 'Parameters id_song and worked are mandatory'], 400);
		}

        $feedbackRecorder = $app[FeedbackRecorder::class];
        $feedbackRecorder->recordFeedback(
			$app['neouser'],
            (int) $req->get('id_song'),
			$req->get('worked'),
			$app['neouser']->range,
			$req->get('pc_status'),
            (float) $req->get('centered_score_rate'),
            (int) $req->get('deviation') ?: null,
			$req->get('transposition')
        );

		//Progressive enhancement: support form submission without AJAX, then refresh the page.
		if (!$req->isXmlHttpRequest())
		{
			return $app->redirect($app->path(
				'transpose_song',
				['id_song' => $req->get('id_song')]
			) . '?fb=' . str_replace(['1', '0'], ['yes', 'no'], (int) $req->get('worked')) . '#feedback');
		}

		return $app->json(['feedback' => 'received'], 200);
	}
}
