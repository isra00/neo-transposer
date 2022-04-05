<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Application\ListSongsWithUserFeedback;
use NeoTransposer\Domain\BookNotExistException;
use NeoTransposer\Model\NotesNotation;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Book page: show list of songs belonging to a given book.
 */
class Book
{
    /**
     * @var NeoApp
     */
    protected $app;

	public function get(Request $req, NeoApp $app, $id_book)
	{
        $this->app = $app;

		if (empty($app['books'][$id_book]))
		{
			$this->abortBookNotExist($id_book);
		}

        try {
            $useCaseListSongsWithUserFeedback = $app[ListSongsWithUserFeedback::class];
            $songs = $useCaseListSongsWithUserFeedback->ListSongsWithUserFeedbackAsArray(
                (int)$id_book,
                $app['neouser']->id_user
            );
        } catch (BookNotExistException $unused)
        {
            $this->abortBookNotExist($id_book);
        }

		$app['locale'] = $app['books'][$id_book]['locale'];
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

		$unhappy = new \NeoTransposer\Model\UnhappyUser($app);

        $userPerformance = $app['neouser']->isLoggedIn() ? $app['neouser']->performance : null;

		$response = new Response($app->render($template, [
			'page_title'	 		=> $app->trans('Songs of the Neocatechumenal Way in %lang%', ['%lang%' => $app['books'][$id_book]['lang_name']]),
			'current_book'	 		=> $app['books'][$id_book],
			'header_link'	 		=> $app->path('book_' . $app['books'][$id_book]['id_book']),
			'songs'			 		=> $songs,
			'show_unhappy_warning'	=> $unhappy->isUnhappyNoAction($app['neouser']),
			'meta_description'		=> $app->trans(
				'Songs and psalms of the Neocatechumenal Way in %lang%. With Neo-Transposer you can transpose them automatically so they will fit your own voice.',
				['%lang%' => $app['books'][$id_book]['lang_name']]
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
