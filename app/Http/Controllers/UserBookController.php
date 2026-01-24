<?php

namespace App\Http\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;

/**
 * Menu for choosing Book. Just that.
 */
final class UserBookController extends Controller
{
    public function get(BookRepository $bookRepository)
    {
        return response()->view('user_book', [
            'books'      => $bookRepository->readAllBooks(),
            'page_title' => __('Choose language'),
            'page_class' => 'page-user-book',
        ]);
    }
}
