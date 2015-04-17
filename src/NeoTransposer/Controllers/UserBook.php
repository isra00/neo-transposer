<?php

namespace NeoTransposer\Controllers;

/**
 * Menu for choosing Book. It is important to remember the book so that the next
 * time the user logs in, he/she will be redirected to it.
 */
class UserBook
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		return $app->render('user_book.twig', array(
			'page_title' => $app->trans('Select language'),
		));
	}
}