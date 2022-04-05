<?php

namespace NeoTransposer\Controllers;

/**
 * Menu for choosing Book. Just that.
 */
class UserBook
{
	public function get(\NeoTransposer\NeoApp $app): string
	{
		/** @todo Sort the languages alphabetically */
		return $app->render('user_book.twig', [
            'books'      => $app['books'],
			'page_title' => $app->trans('Choose language'),
        ]);
	}
}
