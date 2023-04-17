<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\Request;

final class TransposeSongApi
{
    public function __construct(protected NeoApp $app)
    {
    }

	public function handleApiRequest(Request $req, $id_song)
	{
		if (empty($req->get('userToken')))
		{
			throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
		}

		$userRepository = $this->app[UserRepository::class];

		if (!$user = $userRepository->readFromId((int) $req->get('userToken')))
		{
			throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
		}

		$this->app['neouser'] = $user;

		if (empty($user->range->lowest))
		{
			throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException();
		}

        $song = TransposedSong::fromDb($id_song, $this->app);

		$this->app['locale'] = $song->song->bookLocale;
		$this->app['translator']->setLocale($this->app['locale']);

		$song->transpose($user->range);

		json_decode(json_encode($song, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
		$transpositions = [];
		foreach ($song->transpositions as $transposition)
		{
			$transpositions[] = [
				'capo' => $transposition->getCapo(),
				'score' => $transposition->score,
				'chords' => $transposition->chords,
			];
		}

		$responseData = [
			'idSong' 			=> $song->song->idSong,
			'originalChords'	=> $song->song->originalChords,
			'transpositions'	=> $transpositions
		];

		return $this->app->json($responseData, 200);
	}
}