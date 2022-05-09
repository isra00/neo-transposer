<?php

namespace NeoTransposerWeb\Controllers;

use NeoTransposerApp\Domain\Repository\BookRepository;

/**
 * Menu for choosing Book. Just that.
 */
class UserBook
{
	public function get(\NeoTransposerWeb\NeoApp $app): string
	{
        $bookRepository = $app[BookRepository::class];

		return $app->render('user_book.twig', [
            'books'      => $bookRepository->readAllBooks(),
			'page_title' => $app->trans('Choose language'),
        ]);
	}
}
