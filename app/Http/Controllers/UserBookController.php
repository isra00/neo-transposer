<?php

namespace App\Http\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;

/**
 * Menu for choosing Book. Just that.
 */
class UserBookController extends Controller
{
	public function get()
	{
        $bookRepository = app(BookRepository::class);

		return response()->view('user_book', [
            'books'      => $bookRepository->readAllBooks(),
			'page_title' => __('Choose language'),
        ]);
	}
}
