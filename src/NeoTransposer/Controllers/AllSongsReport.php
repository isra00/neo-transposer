<?php

namespace NeoTransposer\Controllers;

use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\TransposedSong;

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
class AllSongsReport
{
	/* 
	 * Report is presented in two columns. This is the sequence number of the
	 * song after which the column break will be done.
	 */
	protected $songForcolumnBreak = array(
		'es' => 109,
		'sw' => 88,
	);

	public function get(\NeoTransposer\NeoApp $app, Request $req)
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
			$song->song_details['title'] = preg_replace('/(.)\[.*\]/', '$1', $song->song_details['title']);

			$songs[] = $song;
		}

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		return $app->render('all_songs_report.twig', array(
			'column_break'	=> $this->songForcolumnBreak[$app['locale']],
			'songs'			=> $songs,
			'your_voice'	=> $your_voice,
			'header_link'	=> $app['url_generator']->generate('book_' . $songs[0]->song_details['id_book']),
		));
	}
}