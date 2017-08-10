<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

/**
 * AJAX Controller to receive/record user feedback. This URL has NO CONTENT.
 */
class ReceiveFeedback
{
	public function post(Request $req, \NeoTransposer\NeoApp $app)
	{
		if ($req->get('id_song') && null !== $req->get('worked'))
		{

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

			//Progressive enhancement: support form submission without AJAX, then refresh the page.
			if (!$req->isXmlHttpRequest())
			{
				return $app->redirect($app->path(
					'transpose_song',
					array('id_song' => $req->get('id_song'))
				) . '?fb=' . str_replace(array('1', '0'), array('yes', 'no'), $worked) . '#feedback');
			}

			return true;
		}
	}
}
