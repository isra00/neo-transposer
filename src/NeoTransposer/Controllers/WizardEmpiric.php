<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;
use \NeoTransposer\Model\SongTextForWizard;
use \NeoTransposer\Model\AutomaticTransposer;
use \NeoTransposer\Model\NotesRange;
use \NeoTransposer\Persistence\UserPersistence;

/**
 * Wizard Empiric: measure the user's voice range through an empirical test.
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
			/** @todo Add HTTP error code */
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

			$app['neouser']->wizard_lowest_attempts = 0;
			$app['neouser']->wizard_highest_attempts = 0;

			return $app->render('wizard_empiric_instructions.twig', array(
				'song_title' => $song_title
			));
		}

		return $this->postLowest($req, $app);
	}

	public function postLowest(Request $req, NeoApp $app)
	{
		$action_no = $action_yes = null;

		//If this was the first time, the user shouldn't click NO.
		if (null === $req->get('can_sing'))
		{
			$action_no = 'lowFirstTime';
		}

		if (empty($app['neouser']->range))
		{
			$app['neouser']->range = new NotesRange;
		}

		//If yes, lower down 1 semitone and retry
		if ('yes' == $req->get('can_sing'))
		{
			$app['neouser']->range->lowest  = $this->nc->transposeNote($app['neouser']->range->lowest, -1);
			$app['neouser']->range->highest = $this->nc->transposeNote($app['neouser']->range->highest, -1);
			$app['neouser']->wizard_lowest_attempts++;
		}

		// If no, we recover the previous one and pass to the next step
		if ('no' == $req->get('can_sing'))
		{
			$app['neouser']->range->lowest  = $this->nc->transposeNote($app['neouser']->range->lowest, +1);
			$app['neouser']->range->highest = $this->nc->transposeNote($app['neouser']->range->highest, +1);

			return $app->redirect($app->path('wizard_empiric_highest'));
		}

		//If too low, next "yes" won't work as usual
		if ('C1' == $app['neouser']->range->lowest)
		{
			$action_yes = 'tooLow';
		}
		
		$tpl = $this->prepareSongForTest('lowest', $app, AutomaticTransposer::FORCE_LOWEST);

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
			$app['neouser']->range->highest = $nc->transposeNote($app['neouser']->range->highest, +1);
			$app['neouser']->wizard_highest_attempts++;
		}

		// If user clicks "yes" many times, he/she will reach the highest possible note (B4)!
		if (end($this->nc->numbered_scale) == $app['neouser']->range->highest)
		{
			$action_yes = 'tooHigh';
		}

		// If not, we recover the last one and pass to the next step
		// ...and if after being alerted that B4 is too much, he/she decides to continue, stop here 
		// and force B4.
		if ('no' == $req->get('can_sing') || $app['neouser']->range->highest == 'C1')
		{
			$app['neouser']->range->highest = $this->nc->transposeNote($app['neouser']->range->highest, -1);
			return $app->redirect($app->path('wizard_finish'));
		}

		$tpl = $this->prepareSongForTest('highest', $app, AutomaticTransposer::FORCE_HIGHEST);

		return $app->render('wizard_empiric_highest.twig', array_merge($tpl, array(
			'action_yes'	=> $action_yes,
			'action_no'		=> $action_no,
		)));
	}

	public function prepareSongForTest($wizard_config_song, NeoApp $app, $forceVoiceLimit=null)
	{
		$wizard_config_song = $app['neoconfig']['voice_wizard'][$app['locale']][$wizard_config_song];

		$transposedSong = \NeoTransposer\Model\TransposedSong::create($wizard_config_song['id_song'], $app);
		$transposedSong->transpose($forceVoiceLimit);

		$transposedChords = $transposedSong->transpositions[0]->chordsForPrint;

		$songText = new SongTextForWizard($wizard_config_song['song_contents']);

		$audioFile = '/static/audio/' . $wizard_config_song['id_song'] . '_' . $transposedSong->transpositions[0]->offset . '.mp3';

		return array(
			'song'			=> $songText->getHtmlTextWithChords($transposedChords),
			'song_title'	=> $transposedSong->song->title,
			'song_key'		=> $transposedChords[0],
			'song_capo'		=> $transposedSong->transpositions[0]->getCapoForPrint(),
			'show_audio'	=> $app['neoconfig']['audio'] && file_exists($app['root_dir'] . '/web' . $audioFile),
			'audio_file'	=> $audioFile,
		);
	}

	public function finish(Request $req, NeoApp $app)
	{
		//Only when wizard is finished, voice range is stored in DB
		$app['neouser']->persistWithVoiceChange($app['db'], $req->getClientIp(), UserPersistence::METHOD_WIZARD);

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		//Link to the book of the current locale, auto-detected.
		$go_to_book = array_keys($app['books'])[
			array_search($app['locale'], array_column($app['books'], 'locale'))
		];

		//If user is unhappy, UnhappyUser will consider this as an action taken.
		$unhappy = new \NeoTransposer\Model\UnhappyUser($app);
		$unhappy->changedVoiceRangeFromWizard($app['neouser']);

		$buttonUrl = empty($app['session']->get('callbackSetUserToken'))
			? 'book_' . $go_to_book
			: 'external_login_finish';

		return $app->render('wizard_finish.twig', array(
			'your_voice'	=> $your_voice,
			'button_url'	=> $app->path($buttonUrl)
		));
	}
}
