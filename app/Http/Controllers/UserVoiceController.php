<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\NotesNotation;

/**
 * Page for the user to set his/her voice range, or to go to the Wizard.
 */
final class UserVoiceController extends Controller
{
    public function get(Request $request)
    {
        if ($request->get('bad_voice_range')) {
            session()->flash('error', __('Are you sure that is your real voice range? If you don\'t know, you can use the assistant to measure it.'));
        }

        $notation = config('nt.languages')[App::getLocale()]['notation'];

        return response()->view('user_voice', [
            'page_title'           => __('Your voice'),
            'page_class'           => 'page-user-voice',
            'scale'                => (new NotesCalculator())->numbered_scale,
            'acoustic_scale'       => NotesCalculator::ACOUSTIC_SCALE,
            'acoustic_scale_nice'  => (new NotesNotation())->getNotationArray(
                NotesCalculator::ACOUSTIC_SCALE,
                $notation
            ),
            'redirect'             => $request->get('redirect') ?? route('book_' . session('user')->id_book),
        ]);
    }
}
