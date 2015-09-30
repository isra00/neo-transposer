<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use NeoTransposer\Controllers;
use \NeoTransposer\NeoApp;
use \NeoTransposer\Model\SongTextForWizard;

/**
 * Wizard Empiric: measures the user's voice range through an empirical test.
 */
class WizardEmpiric
{
	/**
	 * An instance of NotesCalculator
	 * @var \NeoTransposer\Model\NotesCalculator
	 */
	protected $nc;

	public function __construct()
	{
		$this->nc = new \NeoTransposer\Model\NotesCalculator;
	}

	public function lowest(Request $req, NeoApp $app)
	{
		if (!isset($app['neoconfig']['voice_wizard'][$app['locale']]['lowest']))
		{
			return $app->render('error.twig', array(
				'error_title' => $app->trans('Sorry, the voice measure wizard is not available in ' . $app['neoconfig']['languages'][$app['locale']]['name'])
			));
		}

		if ('GET' == $req->getMethod())
		{
			$song_title = $app['db']->fetchColumn(
				'SELECT title FROM song WHERE id_song = ?',
				array($app['neoconfig']['voice_wizard'][$app['locale']]['lowest']['id_song'])
			);

			$app['user']->wizard_lowest_attempts = 0;
			$app['user']->wizard_highest_attempts = 0;

			return $app->render('wizard_empiric_instructions.twig', array(
				'song_title' => $song_title
			));
		}

		return $this->postLowest($req, $app);
	}

	public function postLowest(Request $req, NeoApp $app)
	{
		$action_no = $action_yes = null;

		//If first, time, shouldn't click NO
		if (null === $req->get('can_sing'))
		{
			$action_no = 'lowFirstTime';
		}

		//If yes, lower down 1 semitone and retry
		if ('yes' == $req->get('can_sing'))
		{
			$app['user']->lowest_note = $this->nc->transposeNote($app['user']->lowest_note, -1);
			$app['user']->highest_note = $this->nc->transposeNote($app['user']->highest_note, -1);
			$app['user']->wizard_lowest_attempts++;
		}

		// If no, we recover the previous one and pass to the next step
		if ('no' == $req->get('can_sing'))
		{
			$app['user']->lowest_note = $this->nc->transposeNote($app['user']->lowest_note, +1);
			$app['user']->highest_note = $this->nc->transposeNote($app['user']->highest_note, +1);

			return $app->redirect($app['url_generator']->generate('wizard_empiric_highest'));
		}

		//If too low, next "yes" won't work as usual
		if ('C1' == $app['user']->lowest_note)
		{
			$action_yes = 'tooLow';
		}
		
		$tpl = $this->prepareSongForTest('lowest', $app, false, true);

		return $app->render('wizard_empiric_lowest.twig', array_merge($tpl, array(
			'action_yes'	=> $action_yes,
			'action_no'		=> $action_no,
		)));
	}

	public function highest(Request $req, NeoApp $app)
	{
		$action_no = $action_yes = null;

		//If first, time, shouldn't click NO
		/*if (empty($req->get('can_sing')))
		{
			$action_no = 'lowFirstTime';
		}*/

		//If yes, lower down 1 semitone and retry
		if ('yes' == $req->get('can_sing'))
		{
			$nc = new \NeoTransposer\Model\NotesCalculator;
			$app['user']->highest_note = $nc->transposeNote($app['user']->highest_note, +1);
			$app['user']->wizard_highest_attempts++;
		}

		// If user clicks "yes" many times, we alert...
		if (end($this->nc->numbered_scale) == $app['user']->highest_note)
		{
			$action_yes = 'tooHigh';
		}

		// If no, we recover the last one and pass to the next step
		// ...and if after being alerted that B4 is too much, decides to continue,
		// stop here and force B4.
		if ('no' == $req->get('can_sing') || $app['user']->highest_note == 'C1')
		{
			$app['user']->highest_note = $this->nc->transposeNote($app['user']->highest_note, -1);
			return $app->redirect($app['url_generator']->generate('wizard_finish'));
		}

		$tpl = $this->prepareSongForTest('highest', $app, true);

		return $app->render('wizard_empiric_highest.twig', array_merge($tpl, array(
			'action_yes'	=> $action_yes,
			'action_no'		=> $action_no,
		)));
	}

	public function prepareSongForTest($wizard_config_song, NeoApp $app, $forceHighestNote=false)
	{
		$wizard_config_song = $app['neoconfig']['voice_wizard'][$app['locale']][$wizard_config_song];

		$song = \NeoTransposer\Model\TransposedSong::create($wizard_config_song['id_song'], $app);
		$song->transpose(
			$forceHighestNote,
			!empty($wizard_config_song['override_highest_note']) ? $wizard_config_song['override_highest_note'] : null
		);

		$transposedChords = $song->transpositions[0]->chordsForPrint;

		$songText = new SongTextForWizard($wizard_config_song['song_contents']);

		return array(
			'song'			=> $songText->getHtmlTextWithChords($transposedChords),
			'song_title'	=> $song->song_details['title'],
			'song_key'		=> $transposedChords[0],
			'song_capo'		=> $song->transpositions[0]->getCapoForPrint($app),
		);
	}

	public function finish(Request $req, NeoApp $app)
	{
		$your_voice = $app['user']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		//Redirect to the book of the current locale, auto-detected.
		foreach ($app['books'] as $book)
		{
			if ($book['locale'] == $app['locale'])
			{
				$go_to_book = $book['id_book'];
			}
		}

		//Only when wizard is finished, voice range is stored in DB
		$app['user']->persist($app['db'], $req);

		return $app->render('wizard_finish.twig', array(
			'your_voice'	=> $your_voice,
			'go_to_book'	=> $app['url_generator']->generate('book_' . $go_to_book)
		));
	}
}