<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \NeoTransposer\Model\TransposedSong;

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
class AllSongsReport
{
	/**
	 * HTML report. If dl query string arg is present, the page is offered to
	 * download, included the styles inside the HTML.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp
	 * @return string The rendered view (HTML).
	 */
	public function get(\NeoTransposer\NeoApp $app, Request $req)
	{
		$allTranspositions = $this->getAllTranspositions($app);

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		$tplVars = array(
			'songs'			=> $allTranspositions,
			'your_voice'	=> $your_voice,
			'header_link' 	=> $app->path('book_' . $allTranspositions[0]->song->idBook),
			'page_title'  	=> $app->trans('All transpositions for your voice'),
		);

		if ($req->get('dl'))
		{
			$tplVars['print_css_code'] = file_get_contents($app['root_dir'] . '/web/static/style.css')
			 . file_get_contents($app['root_dir'] . '/web/static/print.css');

			$tplVars['header_link'] = $app['absoluteBasePath'];
		}

		$responseBody = $app->render('all_songs_report.twig', $tplVars);

		if (!$req->get('dl'))
		{
			return $responseBody;
		}
		
		$filename = $app->trans('Transpositions')
		 . '-' . str_replace('#', 'd', $app['neouser']->lowest_note . '-' . $app['neouser']->highest_note)
		 . '.html';

		return new Response($responseBody, 200, array(
			'Cache-Control' 		=> 'private',
			'Content-Type' 			=> 'application/stream',
			'Content-Length' 		=> strlen($responseBody),
			'Content-Disposition' 	=> 'attachment; filename=' . $filename,
		));
	}

	/**
	 * Fetches all the songs from the current book and transposes them.
	 * 
	 * @return array Array of TransposedSong objects.
	 */
	public function getAllTranspositions(\NeoTransposer\NeoApp $app)
	{

		$sql = <<<SQL
SELECT id_song
FROM song 
JOIN book USING (id_book) 
WHERE locale = ? 
AND NOT song.id_song = 118
AND NOT song.id_song = 319
ORDER BY page
SQL;

		$ids = $app['db']->fetchAll($sql, array($app['locale']));

		$songs = array();

		foreach ($ids as $id)
		{
			$song = TransposedSong::create($id['id_song'], $app);
			$song->transpose();

			//Remove bracketed text from song title (used for aclarations)
			$song->song->title = preg_replace('/(.)\[.*\]/', '$1', $song->song->title);

			$songs[] = $song;
		}

		return $songs;
	}
}
