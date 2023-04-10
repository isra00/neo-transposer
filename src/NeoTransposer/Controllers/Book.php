<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Application\ListSongsWithUserFeedback;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Service\UnhappinessManager;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Book page: show list of songs belonging to a given book.
 */
final class Book
{
    /**
     * @var NeoApp
     */
    private $app;

	public function get(Request $req, NeoApp $app, $id_book)
	{
        $this->app = $app;

        try {
            $useCaseListSongsWithUserFeedback = $app[ListSongsWithUserFeedback::class];
            $songs = $useCaseListSongsWithUserFeedback->ListSongsWithUserFeedbackAsArray(
                (int)$id_book,
                $app['neouser']->id_user
            );
        } catch (BookNotExistException)
        {
            $this->abortBookNotExist($id_book);
        }

        $currentBook = $app[BookRepository::class]->readBook((int) $id_book);

		$app['locale'] = $currentBook->locale();
		$app['translator']->setLocale($app['locale']);

        $template = 'book.twig';

        $shouldEncourageFeedback = $app['neouser']->isLoggedIn() && $app['neouser']->shouldEncourageFeedback();

		//If first time or user has reported < 2 fb, show encourage fb banners
		if ($shouldEncourageFeedback)
		{
			$yourVoice = $this->app['neouser']->getVoiceAsString(
				$this->app['translator'],
                new NotesNotation(),
				$this->app['neoconfig']['languages'][$this->app['locale']]['notation']
			);

            $template = 'book_encourage_feedback.twig';
		}

        $showUnhappyWarning = false;

        if ($app['neouser']->isLoggedin())
        {
            $unhappy = $app[UnhappinessManager::class];
            $showUnhappyWarning = $unhappy->isUnhappyNoAction($app['neouser']);
        }

        $userPerformance = $app['neouser']->isLoggedIn() ? $app['neouser']->performance : null;

		$response = new Response($app->render($template, [
			'page_title'	 		=> $app->trans('Songs of the Neocatechumenal Way in %lang%', ['%lang%' => $currentBook->langName()]),
			'current_book'	 		=> $currentBook,
			'all_books'	 		    => $app[BookRepository::class]->readAllBooks(),
			'header_link'	 		=> $app->path('book_' . $currentBook->idBook()),
			'songs'			 		=> $songs,
			'show_unhappy_warning'	=> $showUnhappyWarning,
			'meta_description'		=> $app->trans(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				['%lang%' => $currentBook->langName()]
			),
			'your_voice'			=> $yourVoice ?? null,
			'user_performance'		=> $userPerformance,
			'show_encourage_fb'		=> $shouldEncourageFeedback
        ]), 200);

        //Force no cache
		if ($shouldEncourageFeedback)
		{
			$response->headers->add([
				'Cache-Control' => 'private, must-revalidate, max-age=0',
				'Pragma'		=> 'no-cache',
				'Expires'		=> 'Sat, 26 Jul 1997 05:00:00 GMT'
			]);
		}

		return $response;
	}

    public function abortBookNotExist(int $idBook)
    {
        $this->app->abort(404, "Book $idBook does not exist.");
    }
}
