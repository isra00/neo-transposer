<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * First step of the Wizard: choose a pre-defined voice range. In the next step
 * (WizardEmpiric) that voice range will be refined through empirical tests so
 * the real voice range can be measured.
 */
final class WizardSelectStandardController extends Controller
{
    public function get()
    {
        return response()->view('wizard_select_standard', [
            'page_title' => __('Voice measure wizard'),
            'page_class' => 'voice-wizard',
        ]);
    }

    /**
     * This is a GET request.
     * @todo This should not be a GET request, for security (CSRF!) and RESTfulness.
     */
    public function selectStandard(Request $request)
    {
        $standardVoices = config('nt.voice_wizard.standard_voices');
        $gender = $request->get('gender');

        if (!array_key_exists($gender, $standardVoices)) {
            return redirect()->route('wizard_step1', ['locale' => app()->getLocale()]);
        }

        $user = session('user');
        $user->range = new NotesRange($standardVoices[$gender][0], $standardVoices[$gender][1]);
        $user->wizard_step1 = $gender;
        $user->wizard_lowest_attempts = 0;
        $user->wizard_highest_attempts = 0;

        return response()->view('wizard_empiric_instructions', [
            'page_title'  => __('Voice measure wizard'),
            'page_class'  => 'voice-wizard',
            'form_action' => route('wizard_empiric_lowest', ['locale' => app()->getLocale()]),
        ]);
    }
}
