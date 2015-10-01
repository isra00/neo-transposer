<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

/**
 * Book page: show list of songs belonging to a given book.
 */
class Book
{
	public function get(Request $req, \NeoTransposer\NeoApp $app, $id_book)
	{
		if (empty($app['books'][$id_book]))
		{
			$app->abort(404, "Book $id_book does not exist.");
		}

		$sql = <<<SQL
SELECT song.id_song, slug, page, title, transposition_feedback.worked
FROM song
LEFT JOIN transposition_feedback
	ON transposition_feedback.id_song = song.id_song
	AND transposition_feedback.id_user = ?
WHERE id_book = ?
AND NOT song.id_song = 118
AND NOT song.id_song = 319
ORDER BY page, title
SQL;

		$songs = $app['db']->fetchAll(
			$sql,
			array($app['neouser']->id_user, (int) $id_book)
		);

		$app['locale'] = $app['books'][$id_book]['locale'];

		return $app->render('book.twig', array(
			'page_title'	 	=> $app->trans('Songs of the Neocatechumenal Way in %lang%', array('%lang%' => $app['books'][$id_book]['lang_name'])),
			'current_book'	 	=> $app['books'][$id_book],
			'header_link'	 	=> $app['url_generator']->generate('book_' . $app['books'][$id_book]['id_book']),
			'songs'			 	=> $songs,
			'meta_description'	=> $app->trans(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				array('%lang%' => $app['books'][$id_book]['lang_name'])
			),
		));
	}
}