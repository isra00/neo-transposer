<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;

/**
 * Menu for choosing Book. Just that.
 */
final class UserBook
{
	public function get(\NeoTransposer\NeoApp $app): string
	{
        $bookRepository = $app[BookRepository::class];

		return $app->render('user_book.twig', [
            'books'      => $bookRepository->readAllBooks(),
			'page_title' => $app->trans('Choose language'),
        ]);
	}
}
