<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Service\SongsLister;
use NeoTransposer\Domain\Service\UnhappinessManager;

/**
 * Book page: show list of songs belonging to a given book.
 */
final class BookController extends Controller
{
    //private ?NeoApp $app = null;

	public function get(Request $req, SongsLister $songsLister, BookRepository $bookRepository, UnhappinessManager $unhappinessManager, $bookId)
	{
        try {
            $songs = session('user')->isLoggedIn()
                ? $songsLister->readBookSongsWithUserFeedback((int)$bookId, session('user')->id_user)->asArray()
                : $songsLister->readBookSongs((int)$bookId)->asArray();
        } catch (BookNotExistException)
        {
            $this->abortBookNotExist($bookId);
        }

        $currentBook = $bookRepository->readBook((int) $bookId);

        App::setLocale($currentBook->locale());

        $template = 'book';

        $shouldEncourageFeedback = session('user')->isLoggedIn() && session('user')->shouldEncourageFeedback();

		//If first time or user has reported < 2 fb, show encourage fb banners
		if ($shouldEncourageFeedback)
		{
			$yourVoice = session('user')->getVoiceAsString(
                new NotesNotation(),
				config('nt.languages')[App::getLocale()]['notation']
			);

            $template = 'book_encourage_feedback';
		}

        $showUnhappyWarning = false;

        if (session('user')->isLoggedin())
        {
            $showUnhappyWarning = $unhappinessManager->isUnhappyNoAction(session('user'));
        }

        $userPerformance = session('user')->isLoggedIn() ? session('user')->performance : null;

		$response = response()->view($template, [
			'page_title'	 		=> __('Songs of the Neocatechumenal Way in :lang', ['lang' => $currentBook->langName()]),
			'current_book'	 		=> $currentBook,
			'all_books'	 		    => $bookRepository->readAllBooks(),
			'header_link'	 		=> route('book_' . $currentBook->idBook()),
			'songs'			 		=> $songs,
            'show_unhappy_warning'	=> $showUnhappyWarning,
			'meta_description'		=> __(
				'Songs and psalms of the Neocatechumenal Way in :lang. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				['lang' => $currentBook->langName()]
			),
			'your_voice'			=> $yourVoice ?? null,
			'user_performance'		=> $userPerformance,
			'show_encourage_fb'		=> $shouldEncourageFeedback,
            'page_class' => 'page-book'
        ]);

        //Force no cache
		if ($shouldEncourageFeedback) {
            $response->header('Cache-Control', 'max-age=0, private, must-revalidate');
            $response->header('Pragma','no-cache');
            $response->header('Expires', 'Tue, 8 Mar 1988 07:00:00 GMT');
		}

		return $response;
	}

    public function abortBookNotExist(int $idBook)
    {
        $this->app->abort(404, "Book $idBook does not exist.");
    }
}
