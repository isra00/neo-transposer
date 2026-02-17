<?php

namespace App\Http\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;

final class SitemapController extends Controller
{
	public function get(BookRepository $bookRepository, SongRepository $songRepository)
	{
		$urls = [];

		$languages = array_keys(config('nt.languages'));

		foreach ($languages as $lang)
		{
			$urls[] = [
				'loc' => route('login', ['locale' => $lang]),
			];

			$urls[] = [
				'loc' => route('people-compatible-info', ['locale' => $lang]),
			];

			$urls[] = [
				'loc' => url('/es/manifesto'),
			];
		}

		$books = $bookRepository->readAllBooks();
		foreach ($books as $book)
		{
			$urls[] = [
				'loc' => route('book_' . $book->idBook()),
			];
		}

		$songs = $songRepository->readAllSongs();

		foreach ($songs as $song)
		{
			$urls[] = [
				'loc' => route('transpose_song', ['id_song' => $song->slug]),
			];
		}

		return response()->view('sitemap', ['urls' => $urls])
			->header('Content-Type', 'application/xml');
	}
}
