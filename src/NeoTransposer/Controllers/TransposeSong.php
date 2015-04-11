<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\AutomaticTransposer;
use \NeoTransposer\TranspositionChart;
use \NeoTransposer\NotesCalculator;
use \NeoTransposer\User;

use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransposeSong
{
	public function get(\NeoTransposer\NeoApp $app, Request $req, $id_song)
	{
		//For the teaser
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

		$transData = $this->getTranspositionData($app['user'], $id_song, $app);

		$tpl = array();

		if ($transData['not_equivalent'])
		{
			$printer = $app['chord_printers.get']($transData['song_details']['chord_printer']);

			$transData['not_equivalent'] = $printer->printTransposition($transData['not_equivalent']);
			$transData['not_equivalent']->setCapoForPrint($app);
			$tpl['not_equivalent_difference'] = ($transData['not_equivalent']->deviationFromPerfect > 0)
				? $app->trans('higher')
				: $app->trans('lower');
		}
		
		$nc = new NotesCalculator;
		$your_voice = $app['user']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		$tpl = array_merge($tpl, array(
			/** @todo Eliminar esta duplicidad */
			'current_book'		=> $transData['song_details'],
			'song_details'		=> $transData['song_details'],
			'transpositions'	=> $transData['transpositions'],
			'not_equivalent'	=> $transData['not_equivalent'],
			'original_chords'	=> $transData['original_chords'],
			'next'				=> $transData['next'],
			'your_voice'		=> $your_voice,
			'voice_chart'		=> TranspositionChart::getChart($transData['song_details'], $transData['transpositions'][0], $app['user']),
			'page_title'		=> $app->trans('%song% (Neocatechumenal Way)', array('%song%' => $transData['song_details']['title'])),
			'page_class'		=> 'transpose-song',
			'meta_canonical'	=> $app['url_generator']->generate(
				'transpose_song',
				array('id_song' => $transData['song_details']['slug']),
				UrlGeneratorInterface::ABSOLUTE_URL
			),
			'meta_description'	=> $app->trans(
				'Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
				array('%song%' => $transData['song_details']['title'])
			),
			//'load_social_buttons' => true,
			'feedback'			=> $this->getFeedbackForUser($app['db'], $app['user']->id_user, $transData['song_details']['id_song']),

			//If user's highest note is in the 1st octave, we suggest strongly using the wizard
			'user_first_octave' => (array_search($app['user']->highest_note, $nc->numbered_scale) - array_search($app['user']->lowest_note, $nc->numbered_scale) < 12),
			'url_wizard' 		=> $app['url_generator']->generate('wizard_step1', array('_locale' => $app['locale'])),
		));

		return $app->render('transpose_song.tpl', $tpl);
	}

	public function getTranspositionData(User $user, $id_song, \NeoTransposer\NeoApp $app, $forceHighestNote=false, $forceLowestNote=false, $overrideHighestNote=null)
	{
		$field_id = 'slug';

		if (preg_match('/^\d+$/', $id_song))
		{
			$field_id = 'id_song';
			$id_song = (int) $id_song;
		}

		$song_details = $app['db']->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $field_id = ?",
			array($id_song)
		);

		if (!$song_details) {
			$app->abort(404, "The specified song does not exist or it's not bound to a valid book");
		}

		$original_chords = $app['db']->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array($song_details['id_song'])
		);

		// In PHP 5.5 this can be implemented by array_column()
		array_walk($original_chords, function(&$item) {
			$item = $item['chord'];
		});

		$app['locale'] = $song_details['locale'];

		$next = $app['db']->fetchColumn(
			'SELECT id_song FROM song WHERE id_song > ? AND id_book = ? ORDER BY id_song ASC LIMIT 1',
			array($song_details['id_song'], $song_details['id_book'])
		);

		$transposer = new AutomaticTransposer(
			$user->lowest_note,
			$user->highest_note,
			$song_details['lowest_note'], 
			$overrideHighestNote ? $overrideHighestNote : $song_details['highest_note'], 
			$original_chords,
			$song_details['first_chord_is_tone']
		);

		$transpositions = $transposer->getTranspositions(2, $forceHighestNote, $forceLowestNote);
 		$not_equivalent = $transposer->findAlternativeNotEquivalent();

		//Prepare the chords nicely printed
		/** @todo Pasar esto a AutomaticTransposer? */
		$printer = $app['chord_printers.get']($song_details['chord_printer']);

		$original_chords = $printer->printChordset($original_chords);

		foreach ($transpositions as &$transposition)
		{
			$transposition = $printer->printTransposition($transposition);
			$transposition->setCapoForPrint($app);
		}

		return array(
			'current_book'		=> $song_details,
			'song_details'		=> $song_details,
			'transpositions'	=> $transpositions,
			'not_equivalent'	=> $not_equivalent,
			'original_chords'	=> $original_chords,
			'next'				=> $next,
		);
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