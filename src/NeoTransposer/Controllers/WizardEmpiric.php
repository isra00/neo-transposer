<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\AutomaticTransposer;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\Service\UnhappyUser;
use NeoTransposer\Domain\SongTextForWizard;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wizard Empiric: measure the user's voice range through an empirical test.
 */
class WizardEmpiric
{
	/**
	 * An instance of NotesCalculator
	 * @var NotesCalculator
	 */
	protected $nc;

	public function __construct()
	{
		$this->nc = new NotesCalculator();
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

        //This should not happen, as user should come from selecting a standard range.
		if (empty($app['neouser']->range))
		{
			return $app->redirect($app->path('wizard_select_standard'));
		}

		$action_no = $action_yes = null;

		//If this is the first run, the user shouldn't click NO.
		if (null === $req->get('can_sing'))
		{
			$action_no = 'lowFirstTime';
		}

		//If yes, lower down 1 semitone and retry
		if ('yes' == $req->get('can_sing'))
		{
			$app['neouser']->range->lowest  = $this->nc->transposeNote($app['neouser']->range->lowest, -1);
			$app['neouser']->range->highest = $this->nc->transposeNote($app['neouser']->range->highest, -1);
			$app['neouser']->wizard_lowest_attempts++;
		}

		// If no, we recover the previous highest note as the singer's highest note and redirect to Highest Note test wizard.
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
		
		$tpl = $this->prepareSongForTest('lowest', AutomaticTransposer::FORCE_LOWEST, $app);

		return $app->render('wizard_empiric_lowest.twig', array_merge($tpl, array(
            //Action yes/no means that the yes/no button will not submit the form but run the specified JS function
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
			$nc = new NotesCalculator();
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
			return $this->finish($req, $app);
		}

		$tpl = $this->prepareSongForTest('highest', AutomaticTransposer::FORCE_HIGHEST, $app);

		return $app->render('wizard_empiric_highest.twig', array_merge($tpl, array(
			'action_yes'	=> $action_yes,
			'action_no'		=> $action_no,
		)));
	}

	public function prepareSongForTest($wizard_config_song, $forceVoiceLimit, NeoApp $app): array
	{
		$wizard_config_song = $app['neoconfig']['voice_wizard'][$app['locale']][$wizard_config_song];

        try {
            $transposedSong = TransposedSong::fromDb($wizard_config_song['id_song'], $app);
        } catch (\Exception $e) {
            $app->abort(500, 'The song for the Wizard ' . $wizard_config_song['id_song'] . ' must exist in DB!');
        }

        $transposedSong->transpose($app['neouser']->range, $forceVoiceLimit);

		$transposedChords = $transposedSong->transpositions[0]->chordsForPrint;

		$audioFile = '/static/audio/' . $wizard_config_song['id_song'] . '_' . $transposedSong->transpositions[0]->offset . '.mp3';

		return [
			'song'			=> (new SongTextForWizard($wizard_config_song['song_contents']))->getHtmlTextWithChords($transposedChords),
			'song_title'	=> $transposedSong->song->title,
			'song_key'		=> $transposedChords[0],
			'song_capo'		=> $transposedSong->transpositions[0]->getCapoForPrint(),
			'show_audio'	=> $app['neoconfig']['audio'] && file_exists($app['root_dir'] . '/web' . $audioFile),
			'audio_file'	=> $audioFile,
        ];
	}

    //This could be extracted into a Use Case (application service)
	public function finish(Request $req, NeoApp $app): RedirectResponse
    {
		//Only when wizard is finished, voice range is stored in DB
        $userRepo = $app[UserRepository::class];
        $userRepo->saveWithVoiceChange($app['neouser'], User::METHOD_WIZARD);

		//If user is unhappy, UnhappyUser will consider this as an action taken.
		$app[UnhappyUser::class]->changedVoiceRangeFromWizard($app['neouser']);

		$redirectPath = 'external_login_finish';

		if (empty($app['session']->get('callbackSetUserToken')))
		{
			$redirectPath = 'book_' . $app[BookRepository::class]->readIdBookFromLocale($app['locale']);
		}

		return $app->redirect($app->path($redirectPath, ['wizardFinished' => 1]));
	}
}
