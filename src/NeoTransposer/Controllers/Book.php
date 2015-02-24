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
			'page_title'	 	=> $app->trans('Songs of the Neocatechumenal Way in %lang%', array('%lang%' => $app['books'][$id_book]['lang_name'])),
			'current_book'	 	=> $app['books'][$id_book],
			'songs'			 	=> $songs,
			'meta_description'	=> $app->trans(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				array('%lang%' => $app['books'][$id_book]['lang_name'])
			),
			'rating'			=> $this->getBookRating($id_book, count($songs), $app)
		));
	}

	protected function getBookRating($id_book, $songs, \NeoTransposer\NeoApp $app)
	{
		//"Users rated" = # of users who use this book
		$users = $app['db']->fetchColumn(
			'SELECT COUNT(id_user) FROM user WHERE id_book = ?',
			array($id_book)
		);

		return array(
			//More songs => higher rating, always btw 4 and 5
			'rating'		=> 4 + (1 / (220 - $songs)),
			'users'			=> $users,
			'book_title'	=> $app->trans('Songs of the Neocatechumenal Way in %lang%', array('%lang%' => $app['books'][$id_book]['lang_name']))
		);
	}
}