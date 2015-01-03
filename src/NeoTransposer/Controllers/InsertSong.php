<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\User;

class InsertSong
{
	public function get(Request $request, \NeoTransposer\NeoApp $app, $tpl_vars=array())
	{
		return $app->render('insert_song.tpl', $tpl_vars);
	}

	public function post(Request $request, \NeoTransposer\NeoApp $app)
	{
		$app['db']->insert('song', array(
			'id_book' => $request->get('id_book'),
			'page' => $request->get('page'),
			'title' => $request->get('title'),
			'lowest_note' => $request->get('lowest_note'),
			'highest_note' => $request->get('highest_note'),
		));

		$id_song = $app['db']->lastInsertId();

		foreach ($request->get('chords') as $position=>$chord)
		{
			if (strlen($chord))
			{
				$app['db']->insert('song_chord', array(
					'id_song' => $id_song,
					'chord' => $chord,
					'position' => $position
				));
			}
		}

		$app->addNotification('success', 'Song inserted');

		return $this->get($request, $app);
	}
}