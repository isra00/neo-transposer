<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\User;
use \Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Administrator's Insert Song form.
 */
class InsertSong
{
	/**
	 * A DB connection
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $db;

	public function get(Request $request, \NeoTransposer\NeoApp $app, $tpl_vars=array())
	{
		$app['locale'] = 'es';

		return $app->render('insert_song.twig', $tpl_vars);
	}

	public function post(Request $request, \NeoTransposer\NeoApp $app)
	{
		$this->db = $app['db'];

		$app['db']->insert('song', array(
			'id_book' => $request->get('id_book'),
			'page' => $request->get('page'),
			'title' => $request->get('title'),
			'lowest_note' => $request->get('lowest_note'),
			'highest_note' => $request->get('highest_note'),
			'first_chord_is_tone' => str_replace('on', '1', $request->get('first_chord_is_key')),
			'slug' => $this->getSlug($request),
		));

		$id_song = $this->db->lastInsertId();

		foreach ($request->get('chords') as $position=>$chord)
		{
			if (strlen($chord))
			{
				$this->db->insert('song_chord', array(
					'id_song' => $id_song,
					'chord' => $chord,
					'position' => $position
				));
			}
		}

		$app->addNotification('success', 'Song inserted');

		return $this->get(
			$request, 
			$app, 
			array('id_book' => $request->get('id_book'))
		);
	}

	/**
	 * Use this function to set the slug on those songs which don't have.
	 * 
	 * @param  \Doctrine\DBAL\Connection $db A DB connection
	 */
	function slugizedb(\Doctrine\DBAL\Connection $db)
	{
		$songs = $db->fetchAll("SELECT * FROM song WHERE slug IS NULL OR slug = ''");
		foreach ($songs as $song)
		{
			$db->update(
				'song',
				array('slug' => $this->urlize($song['title'])),
				array('id_song' => $song['id_song'])
			);
		}
	}

	protected function getSlug(Request $request)
	{
		$candidate = $this->urlize($request->get('title'));
		$already = $this->checkSlug($candidate);

		//If there is a song with that slug, try to append the language name
		if (!empty($already))
		{
			$lang_name = $this->db->fetchColumn(
				'SELECT lang_name FROM book WHERE id_book = ?',
				array($request->get('id_book'))
			);
			$candidate = $candidate . '-' . $this->urlize($lang_name);
			$already = $this->checkSlug($candidate);

			if ($already)
			{
				throw new ConflictHttpException('There is already a song with that slug in that book!');
			}
		}

		return $candidate;

	}

	protected function checkSlug($candidate)
	{
		return $this->db->fetchAssoc(
			'SELECT id_song, slug FROM song WHERE slug = ?',
			array($candidate)
		);
	}

	protected function urlize($string)
	{
		$hyphenize = array(' ', ',', '.', ':', '!', '¡', '¿', '?', '(', ')', '[', ']');

		//La ñ la conservamos
		$flatten_letters = array(
			'Á' => 'a',
			'á' => 'a',
			'É' => 'e',
			'é' => 'e',
			'Í' => 'i',
			'í' => 'i',
			'Ó' => 'o',
			'ó' => 'o',
			'Ú' => 'u',
			'ú' => 'u',
			'ö' => 'o',
			'ü' => 'u',
			'ª' => 'a',
			'º' => 'o',
		);

		$string = strtolower(trim($string));
		$string = str_replace($hyphenize, '-', $string);
		$string = str_replace(
			array_keys($flatten_letters), 
			array_values($flatten_letters), 
			$string
		);
		$string = preg_replace('/(\-\-+)/', '-', $string);
		$string = preg_replace('/^\-/', '', $string);
		$string = preg_replace('/\-$/', '', $string);
		return $string;
	}
}