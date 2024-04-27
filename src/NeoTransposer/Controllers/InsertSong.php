<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Service\SongCreator;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administrator's Insert Song form.
 */
final class InsertSong
{
	public function get(NeoApp $app, $tpl_vars=[])
	{
		$app['locale'] = 'es';

		return $app->render('insert_song.twig', array_merge($tpl_vars, [
			'page_title' => 'Insert Song · ' . $app['neoconfig']['software_name'],
            'all_books'  => $app[BookRepository::class]->readAllBooks()
		]), false);
	}

	public function post(Request $request, NeoApp $app)
	{
        //Remove empty chord inputs leaving a sequence in the array keys
        $songChords = [];
		foreach ($request->get('chords') as $chord)
		{
			if ((string)$chord !== '')
			{
                $songChords[] = $chord;
			}
		}

        $songCreator = new SongCreator($app[SongRepository::class], $app[BookRepository::class]);

        $songCreator->createSong(
			$request->get('id_book'),
			$request->get('page') ? (int)$request->get('page') : null,
			$request->get('title'),
			strtoupper((string) $request->get('lowest_note')),
			strtoupper((string) $request->get('highest_note')),
			strtoupper((string) $request->get('people_lowest_note')),
			strtoupper((string) $request->get('people_highest_note')),
			(bool) $request->get('first_chord_is_key'),
            $songChords
        );

		$app->addNotification('success', 'Song inserted');

		return $this->get(
			$app,
			['id_book' => $request->get('id_book')]
		);
	}
}
