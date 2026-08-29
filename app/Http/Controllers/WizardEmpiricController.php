<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\Service\UnhappinessManager;
use NeoTransposer\Domain\SongTextForWizard;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\Transposer;

/**
 * Wizard Empiric: measure the user's voice range through an empirical test.
 */
final class WizardEmpiricController extends Controller
{
    public function __construct(
        private readonly NotesCalculator $nc,
        private readonly UserRepository $userRepository,
        private readonly UnhappinessManager $unhappinessManager,
        private readonly BookRepository $bookRepository,
    ) {
    }

    public function lowest(Request $request)
    {
        $locale = app()->getLocale();
        $wizardConfig = config('nt.voice_wizard');

        if (!isset($wizardConfig[$locale]['lowest'])) {
            return response()->view('error', [
                'page_title'  => __('Error'),
                'error_title' => __('Sorry, the voice measure wizard is not available in ' . config('nt.languages')[$locale]['name']),
            ], 404);
        }

        $user = session('user');

        // This should not happen, as user should come from selecting a standard range.
        if (empty($user->range)) {
            return redirect()->route('wizard_select_standard', ['locale' => $locale]);
        }

        $action_no = $action_yes = null;

        // If this is the first run, the user shouldn't click NO.
        if ($request->get('can_sing') === null) {
            $action_no = 'lowFirstTime';
        }

        // If yes, lower down 1 semitone and retry
        if ($request->get('can_sing') == 'yes') {
            $user->range->lowest = $this->nc->transposeNote($user->range->lowest, -1);
            $user->range->highest = $this->nc->transposeNote($user->range->highest, -1);
            $user->wizard_lowest_attempts++;
        }

        // If no, we recover the previous highest note as the singer's highest note and redirect to Highest Note test wizard.
        if ($request->get('can_sing') == 'no') {
            $user->range->lowest = $this->nc->transposeNote($user->range->lowest, +1);
            $user->range->highest = $this->nc->transposeNote($user->range->highest, +1);

            return redirect()->route('wizard_empiric_highest', ['locale' => $locale]);
        }

        // If too low, next "yes" won't work as usual
        if ($user->range->lowest == 'C1') {
            $action_yes = 'tooLow';
        }

        $tpl = $this->prepareSongForTest('lowest', Transposer::FORCE_LOWEST);

        return response()->view('wizard_empiric_lowest', array_merge($tpl, [
            'page_title' => __('Voice measure wizard'),
            'page_class' => 'wizard-empiric',
            'action_yes' => $action_yes,
            'action_no'  => $action_no,
        ]));
    }

    public function highest(Request $request)
    {
        $user = session('user');
        $action_no = $action_yes = null;

        // If yes, raise up 1 semitone and retry
        if ($request->get('can_sing') == 'yes') {
            $user->range->highest = $this->nc->transposeNote($user->range->highest, +1);
            $user->wizard_highest_attempts++;
        }

        // If user clicks "yes" many times, he/she will reach the highest possible note (B4)!
        if (end($this->nc->numbered_scale) == $user->range->highest) {
            $action_yes = 'tooHigh';
        }

        // If not, we recover the last one and pass to the next step
        // ...and if after being alerted that B4 is too much, he/she decides to continue, stop here
        // and force B4.
        if ($request->get('can_sing') == 'no' || $user->range->highest == 'C1') {
            $user->range->highest = $this->nc->transposeNote($user->range->highest, -1);

            return $this->finish();
        }

        $tpl = $this->prepareSongForTest('highest', Transposer::FORCE_HIGHEST);

        return response()->view('wizard_empiric_highest', array_merge($tpl, [
            'page_title' => __('Voice measure wizard'),
            'page_class' => 'wizard-empiric',
            'action_yes' => $action_yes,
            'action_no'  => $action_no,
        ]));
    }

    private function prepareSongForTest(string $wizardConfigSong, int $forceVoiceLimit): array
    {
        $locale = app()->getLocale();
        $wizardConfig = config('nt.voice_wizard')[$locale][$wizardConfigSong];
        $user = session('user');

        $transposedSong = TransposedSong::fromDb($wizardConfig['id_song']);
        $transposedSong->transpose($user->range, $forceVoiceLimit);

        $transposedChords = $transposedSong->transpositionsCentered[0]->chordsForPrint;

        $audioFile = '/static/audio/' . $wizardConfig['id_song'] . '_' . $transposedSong->transpositionsCentered[0]->offset . '.mp3';

        return [
            'song'       => (new SongTextForWizard($wizardConfig['song_contents']))->getHtmlTextWithChords($transposedChords),
            'song_title' => $transposedSong->song->title,
            'song_key'   => $transposedChords[0],
            'song_capo'  => $transposedSong->transpositionsCentered[0]->getCapoForPrint(),
            'show_audio' => config('nt.audio') && file_exists(public_path($audioFile)),
            'audio_file' => $audioFile,
        ];
    }

    private function finish()
    {
        $user = session('user');
        $locale = app()->getLocale();

        // Only when wizard is finished, voice range is stored in DB
        $this->userRepository->saveWithVoiceChange($user, User::METHOD_WIZARD);

        // If user is unhappy, UnhappyUser will consider this as an action taken.
        $this->unhappinessManager->changedVoiceRangeFromWizard($user);

        $redirectRoute = 'book_' . $this->bookRepository->readIdBookFromLocale($locale);

        return redirect()->route($redirectRoute, ['wizardFinished' => 1]);
    }
}
