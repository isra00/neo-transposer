<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\Model\TransposedSong;
use \NeoTransposer\Model\TranspositionChart;
use \NeoTransposer\Model\NotesCalculator;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Transpose Song page: transpose the given song for the singer's voice range.
 */
class TransposeSong
{
	public function get(\NeoTransposer\NeoApp $app, Request $req, $id_song)
	{
		//For the teaser (not logged in), transpose for a standard male voice
		if (!$app['neouser']->isLoggedIn())
		{
			$app['neouser']->lowest_note = 'B1';
			$app['neouser']->highest_note = 'F#3';
		}
		else
		{
			//If null user, redirect to User Settings
			if (empty($app['neouser']->lowest_note))
			{
				$app->setLocaleAutodetect($req);
				
				return $app->redirect($app['url_generator']->generate(
					'user_voice', 
					array('_locale' => $app['locale'])
				));
			}
		}

		$transposedSong = TransposedSong::create($id_song, $app);
		
		$app['locale'] = $transposedSong->song->bookLocale;
		$app['translator']->setLocale($app['locale']);

		$transposedSong->transpose();

		$tpl = array();

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		$nc = new NotesCalculator;

		$user_first_octave = (
			array_search($app['neouser']->highest_note, $nc->numbered_scale)
			- array_search($app['neouser']->lowest_note, $nc->numbered_scale)
			< 12
		);

		return $app->render('transpose_song.twig', array_merge($tpl, array(
			'song'				=> $transposedSong,
			'your_voice'		=> $your_voice,
			'voice_chart'		=> TranspositionChart::getChart($transposedSong->song, $transposedSong->transpositions[0], $app['neouser']),
			'page_title'		=> $app->trans('%song% (Neocatechumenal Way)', array('%song%' => $transposedSong->song->title)),
			'header_link'		=> $app['url_generator']->generate('book_' . $transposedSong->song->idBook),
			'meta_canonical'	=> $app['url_generator']->generate(
				'transpose_song',
				array('id_song' => $transposedSong->song->slug),
				UrlGeneratorInterface::ABSOLUTE_URL
			),
			'meta_description'	=> $app->trans(
				'Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
				array('%song%' => $transposedSong->song->title)
			),
			'feedback'			=> $this->getFeedbackForUser($app['db'], $app['neouser']->id_user, $transposedSong->song->idSong),

			//If user's highest note is in the 1st octave, we suggest strongly using the wizard
			'user_first_octave' => $user_first_octave,
			'url_wizard' 		=> $app['url_generator']->generate('wizard_step1', array('_locale' => $app['locale'])),

			//Non-JS browsers show message after clicking on feedback
			'non_js_fb'			=>  $req->get('fb')
		)));
	}

	protected function getFeedbackForUser(\Doctrine\DBAL\Connection $db, $id_user, $id_song)
	{
		$worked = $db->fetchColumn(
			'SELECT worked FROM transposition_feedback WHERE id_user = ? AND id_song = ?',
			array($id_user, $id_song)
		);
		return str_replace(array('1', '0'), array('yes', 'no'), $worked);
	}
}