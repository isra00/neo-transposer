<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

class ChordCorrectionPanel
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		$app['locale'] = 'es';

		$adminTools = new \NeoTransposer\Model\AdminTools($app);
		$problematic = $adminTools->checkChordOrder();

		if ('NO inconsistences found :-)' == $problematic)
		{
			return 'No inconsistent chord positions found :-)';
		}

		$chords = $app['db']->fetchAll(
			'SELECT * FROM song_chord JOIN song USING (id_song) WHERE id_song IN (?) ORDER BY id_song, position',
			array(array_keys($problematic)),
            array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
		);

		$songs = [];
		$count = 0;
		foreach ($chords as $chord)
		{
			if (!isset($songs[$chord['id_song']]))
			{
				$songs[$chord['id_song']] = array(
					'id_song'	=> $chord['id_song'],
					'id_book'	=> $chord['id_book'],
					'page'		=> $chord['page'],
					'title' 	=> $chord['title'],
					'chords' 	=> []
				);
			}

			$songs[$chord['id_song']]['chords'][] = array(
				'chord' => $chord['chord'],
				'position' => $chord['position'],
			);

			$songs[$chord['id_song']]['image'] = ($chord['id_book'] == 1)
				? "/resucito-imgs/sw/{$chord['page']}.jpg"
				: "/resucito-imgs/es/" . str_pad($chord['page'], 3, '0', STR_PAD_LEFT) . ".pdf";

			if ($count > 50)
			{
				break;
			}

			$count++;
		}

		return $app->render('chord_correction_panel.twig', array(
			'songs' => $songs
		));
	}

	public function post(Request $req, \NeoTransposer\NeoApp $app)
	{
		foreach ($req->request as $key=>$position)
		{
			if (preg_match('/^(\d+)\_(.*)$/', $key, $match))
			{
				$id_song = $match[1];
				$chord = $match[2];

				$app['db']->executeQuery("UPDATE song_chord SET position=$position WHERE id_song = $id_song AND chord='$chord'");
			}
		}

		return $app->redirect($app->path($req->attributes->get('_route')));
	}
}
