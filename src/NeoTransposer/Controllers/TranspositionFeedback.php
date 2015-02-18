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
			$app['db']->insert(
				'transposition_feedback',
				array(
					'id_song' => $req->get('id_song'),
					'id_user' => $app['user']->id_user,
					'worked' => (int) $req->get('worked')
				)
			);

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