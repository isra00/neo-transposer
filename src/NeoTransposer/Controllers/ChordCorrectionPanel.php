<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChordCorrectionPanel
{
	public function get(Request $req, \NeoTransposer\NeoApp $app)
	{
		$app['locale'] = 'es';

		$adminTools = new \NeoTransposer\Model\AdminTools;
		$problematic = $adminTools->checkChordOrder($app);

		if (empty($problematic))
		{
			return 'No inconsistent chord positions found :-)';
		}

		$chords = $app['db']->fetchAll(
			'SELECT * FROM song_chord JOIN song USING (id_song) WHERE id_song IN ('
			. implode(', ', array_keys($problematic)) . ') ORDER BY id_song, position'
		);

		$songs = array();
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
					'chords' 	=> array()
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

		return $app->redirect($app['url_generator']->generate($req->attributes->get('_route')));
	}
}