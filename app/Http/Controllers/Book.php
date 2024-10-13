<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Service\SongsLister;

/**
 * Book page: show list of songs belonging to a given book.
 */
final class Book extends Controller
{
    //private ?NeoApp $app = null;

	public function get(Request $req, SongsLister $songsLister, BookRepository $bookRepository, $bookId)
	{

        try {
            $songs = false /* $app['neouser']->id_user */
                ? $songsLister->readBookSongsWithUserFeedback((int)$bookId, $app['neouser']->id_user)->asArray()
                : $songsLister->readBookSongs((int)$bookId)->asArray();
        } catch (BookNotExistException)
        {
            $this->abortBookNotExist($bookId);
        }

        $currentBook = $bookRepository->readBook((int) $bookId);

        App::setLocale($currentBook->locale());

        $template = 'book.twig';

        $shouldEncourageFeedback = false;
        //$shouldEncourageFeedback = $app['neouser']->isLoggedIn() && $app['neouser']->shouldEncourageFeedback();

		//If first time or user has reported < 2 fb, show encourage fb banners
		if ($shouldEncourageFeedback)
		{
			$yourVoice = $this->app['neouser']->getVoiceAsString(
                new NotesNotation(),
				config('nt.languages')[App::getLocale()]['notation']
			);

            $template = 'book_encourage_feedback.twig';
		}

        $showUnhappyWarning = false;

        /*if ($app['neouser']->isLoggedin())
        {
            $unhappy = $app[UnhappinessManager::class];
            $showUnhappyWarning = $unhappy->isUnhappyNoAction($app['neouser']);
        }*/

        //$userPerformance = $app['neouser']->isLoggedIn() ? $app['neouser']->performance : null;
        $userPerformance = null;

		$response = response()->view($template, [
			'page_title'	 		=> __('Songs of the Neocatechumenal Way in %lang%', ['%lang%' => $currentBook->langName()]),
			'current_book'	 		=> $currentBook,
			'all_books'	 		    => $bookRepository->readAllBooks(),
			'header_link'	 		=> url('book_' . $currentBook->idBook()),
			'songs'			 		=> $songs,
			'show_unhappy_warning'	=> $showUnhappyWarning,
			'meta_description'		=> __(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				['%lang%' => $currentBook->langName()]
			),
			'your_voice'			=> $yourVoice ?? null,
			'user_performance'		=> $userPerformance,
			'show_encourage_fb'		=> $shouldEncourageFeedback
        ]);

        //Force no cache
		if ($shouldEncourageFeedback)
		{
            $response->header('Cache-Control', 'private, must-revalidate, max-age=0');
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
