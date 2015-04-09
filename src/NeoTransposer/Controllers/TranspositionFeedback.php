<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranspositionFeedback
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

			$app['db']->executeUpdate($sql, array(
				$req->get('id_song'),
				$app['user']->id_user,
				(int) $req->get('worked'),
				$app['user']->lowest_note,
				$app['user']->highest_note,
				$req->get('id_song'),
				$app['user']->id_user,
				(int) $req->get('worked'),
				$app['user']->lowest_note,
				$app['user']->highest_note,
			));

			//Progressive enhancement: supports form submission without AJAX
			if (!$req->isXmlHttpRequest())
			{
				/** @todo En vez de #feedback, ?feedback y mostrar mensajes!!! */
				return $app->redirect($app['url_generator']->generate(
					'transpose_song',
					array('id_song' => $req->get('id_song'))
				) . '#feedback');
			}

			return true;
		}
	}
}		