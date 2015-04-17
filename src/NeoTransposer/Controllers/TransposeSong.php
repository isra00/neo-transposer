<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\Model\TransposedSong;
use \NeoTransposer\Model\TranspositionChart;
use \NeoTransposer\Model\NotesCalculator;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
class TransposeSong
{
	public function get(\NeoTransposer\NeoApp $app, Request $req, $id_song)
	{
		//For the teaser we transpose for a standard male voice
		if (!$app['user']->isLoggedIn())
		{
			$app['user']->lowest_note = 'B1';
			$app['user']->highest_note = 'F#3';
		}
		else
		{
			//If null user, redirect to User Settings
			if (empty($app['user']->lowest_note))
			{
				$app->setLocaleAutodetect($req);
				
				return $app->redirect($app['url_generator']->generate(
					'user_settings', 
					array('_locale' => $app['locale'])
				));
			}
		}

		$song = TransposedSong::create($id_song, $app);
		$app['locale'] = $song->song_details['locale'];
		$song->transpose();

		$tpl = array();

		if ($song->not_equivalent)
		{
			$printer = $app['chord_printers.get']($song->song_details['chord_printer']);

			$song->not_equivalent = $printer->printTransposition($song->not_equivalent);
			$song->not_equivalent->setCapoForPrint($app);
			$tpl['not_equivalent_difference'] = ($song->not_equivalent->deviationFromPerfect > 0)
				? $app->trans('higher')
				: $app->trans('lower');
		}
		
		$your_voice = $app['user']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		$nc = new NotesCalculator;

		return $app->render('transpose_song.twig', array_merge($tpl, array(
			'song'				=> $song,
			'your_voice'		=> $your_voice,
			'voice_chart'		=> TranspositionChart::getChart($song->song_details, $song->transpositions[0], $app['user']),
			'page_title'		=> $app->trans('%song% (Neocatechumenal Way)', array('%song%' => $song->song_details['title'])),
			'header_link'		=> $app['url_generator']->generate('book_' . $song->song_details['id_book']),
			'meta_canonical'	=> $app['url_generator']->generate(
				'transpose_song',
				array('id_song' => $song->song_details['slug']),
				UrlGeneratorInterface::ABSOLUTE_URL
			),
			'meta_description'	=> $app->trans(
				'Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
				array('%song%' => $song->song_details['title'])
			),
			'feedback'			=> $this->getFeedbackForUser($app['db'], $app['user']->id_user, $song->song_details['id_song']),

			//If user's highest note is in the 1st octave, we suggest strongly using the wizard
			'user_first_octave' => (array_search($app['user']->highest_note, $nc->numbered_scale) - array_search($app['user']->lowest_note, $nc->numbered_scale) < 12),
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