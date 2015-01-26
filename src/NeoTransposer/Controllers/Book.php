<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

class Book
{
	public function get(Request $req, \NeoTransposer\NeoApp $app, $id_book)
	{
		if (empty($app['books'][$id_book]))
		{
			$app->abort(404, "Book $id_book does not exist.");
		}

		$songs = $app['db']->fetchAll(
			'SELECT * FROM song WHERE id_book = ? AND NOT id_song = 118 AND NOT id_song = 319 ORDER BY page, title',
			array((int) $id_book)
		);

		$app['locale'] = $app['books'][$id_book]['locale'];

		return $app->render('book.tpl', array(
			'page_title'	 => $app->trans('Songs of the Neocatechumenal Way in %lang%', array('%lang%' => $app['books'][$id_book]['lang_name'])),
			'current_book'	 => $app['books'][$id_book],
			'songs'			 => $songs
		));
	}
}