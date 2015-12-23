<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
	time
) VALUES (?, ?, ?, ?, ?, NOW())
ON DUPLICATE KEY UPDATE
	id_song = ?,
	id_user = ?,
	worked = ?,
	user_lowest_note = ?,
	user_highest_note = ?,
	time = NOW()
SQL;
			$worked = (int) $req->get('worked');

			$app['db']->executeUpdate($sql, array(
				$req->get('id_song'),
				$app['neouser']->id_user,
				$worked,
				$app['neouser']->lowest_note,
				$app['neouser']->highest_note,
				$req->get('id_song'),
				$app['neouser']->id_user,
				$worked,
				$app['neouser']->lowest_note,
				$app['neouser']->highest_note,
			));

			//Progressive enhancement: supports form submission without AJAX
			if (!$req->isXmlHttpRequest())
			{
				return $app->redirect($app['url_generator']->generate(
					'transpose_song',
					array('id_song' => $req->get('id_song'))
				) . '?fb=' . str_replace(array('1', '0'), array('yes', 'no'), $worked) . '#feedback');
			}

			return true;
		}
	}
}		