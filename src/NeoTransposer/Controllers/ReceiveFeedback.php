<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AJAX Controller to receive/record user feedback. This URL has NO CONTENT.
 */
class ReceiveFeedback
{
	public function post(Request $req, \NeoTransposer\NeoApp $app): Response
	{
		//This usually happens when the sessions times out (=> HTTP status 408).
		if (!$app['neouser']->isLoggedIn())
		{
            //The JSON body is superfluous since JS reads the status code only.
			return ($req->isXmlHttpRequest())
				? $app->json(['error' => 'notLoggedIn'], 408)
				: $app->redirect($req->server->get('HTTP_REFERER'));
		}

		if (empty($req->get('id_song')) || null === $req->get('worked'))
		{
			return $app->json(['error' => 'Parameters id_song and worked are mandatory'], 400);
		}

		$sql = <<<SQL
INSERT INTO transposition_feedback (
	id_song,
	id_user,
	worked,
	user_lowest_note,
	user_highest_note,
	time,
	transposition,
	pc_status,
	deviation_from_center,
	centered_score_rate
) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
	id_song = ?,
	id_user = ?,
	worked = ?,
	user_lowest_note = ?,
	user_highest_note = ?,
	time = NOW(),
	transposition = ?,
	pc_status = ?,
	deviation_from_center = ?,
	centered_score_rate = ?
SQL;
		$worked = (int) $req->get('worked');

		$app['db']->executeUpdate($sql, array(
			$req->get('id_song'),
			$app['neouser']->id_user,
			$worked,
			$app['neouser']->range->lowest,
			$app['neouser']->range->highest,
			$req->get('transposition'),
			$req->get('pc_status'),
			$req->get('deviation') ? intval($req->get('deviation')) : null,
			$req->get('centered_score_rate'),

			$req->get('id_song'),
			$app['neouser']->id_user,
			$worked,
			$app['neouser']->range->lowest,
			$app['neouser']->range->highest,
			$req->get('transposition'),
			$req->get('pc_status') ?: null,
			$req->get('deviation') ? intval($req->get('deviation')) : null,
			$req->get('centered_score_rate'),
		));

		$unhappy = new \NeoTransposer\Model\UnhappyUser($app);
		$unhappy->setUnhappy($app['neouser']);

		$userPersistence = new \NeoTransposer\Persistence\UserPersistence($app['db']);
		$app['neouser']->feedbacksReported = $userPersistence->fetchUserPerformance($app['neouser'])['reports'];

		//Progressive enhancement: support form submission without AJAX, then refresh the page.
		if (!$req->isXmlHttpRequest())
		{
			return $app->redirect($app->path(
				'transpose_song',
				array('id_song' => $req->get('id_song'))
			) . '?fb=' . str_replace(array('1', '0'), array('yes', 'no'), $worked) . '#feedback');
		}

		return $app->json(['feedback' => 'received'], 200);
	}
}
