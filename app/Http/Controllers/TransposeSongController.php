<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use NeoTransposer\Controllers\TransposeSongApi;
use NeoTransposer\Domain\Exception\SongNotExistException;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\PeopleCompatibleCalculation;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\TranspositionChart;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Illuminate\Http\Request;

/**
 * Transpose Song page: transpose the given song for the singer's voice range.
 */
final class TransposeSongController extends Controller
{
    public function get(Request $req, IpToLocaleResolver $ipToLocaleResolver, FeedbackRepository $feedbackRepository, BookRepository $bookRepository, $id_song)
    {
        $transposedSong = null;

        //For the teaser (not logged in), transpose for a standard male voice
        if (!session('user')->isLoggedIn()) {
            session('user')->range = new NotesRange('B1', 'F#3');
        } elseif (empty(session('user')->range->lowest)) {
            //If null user, redirect to User Settings in the selected song's language.
            $this->setLocaleAutodetect($req, $ipToLocaleResolver);
            return redirect()->route('user_voice', ['locale' => App::getLocale()]);
        }

        try {
            $transposedSong = TransposedSong::fromDb($id_song);
        } catch (SongNotExistException) {
            $app->abort(404, "Song $id_song does not exist.");
        }

        $transposedSong->transpose(session('user')->range);

        App::setLocale($transposedSong->song->bookLocale);

        $your_voice = session('user')->getVoiceAsString(
            new NotesNotation(),
            config('nt.languages')[App::getLocale()]['notation']
        );

        $nc = new NotesCalculator();

        $transpositionChart = $this->generateTranspositionChart($nc, $transposedSong);

        $tplVars = [];

        if ($transposedSong->getPeopleCompatible() !== null) {

            $peopleCompatibleMsgUntranslated = '';
            switch ($transposedSong->getPeopleCompatibleStatus())
            {
                case PeopleCompatibleCalculation::ADJUSTED_WELL:
                    $peopleCompatibleMsgUntranslated = 'This other transposition, though a bit :difference, fits well the people of the assembly.';
                    $tplVars['peopleCompatibleClass'] = 'star';
                    break;
                case PeopleCompatibleCalculation::ADJUSTED_WIDER:
                    $peopleCompatibleMsgUntranslated = 'This other transposition, though a bit :difference, may probably fit better the people of the assembly.';
                    break;
                case PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE:
                    $peopleCompatibleMsgUntranslated = 'The chords given above are good for your voice, but probably too high for the assembly. The following transposition is :difference, though still high for some people of the assembly.';
                    break;
                case PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE:
                    $peopleCompatibleMsgUntranslated = 'The chords given above are good for your voice, but probably too low for the assembly. The following transposition is :difference, though still low for some people of the assembly.';
                    break;
            }

            $tplVars['peopleCompatibleMsg'] = __(
                $peopleCompatibleMsgUntranslated,
                [
                    'difference' => ($transposedSong->getPeopleCompatible()->deviationFromCentered > 0)
                        ? __('higher')
                        : __('lower')
                ]
            );

            if ($transposedSong->getPeopleCompatible()->score < $transposedSong->transpositions[0]->score) {
                $tplVars['peopleCompatibleMsg'] .= ' ' . __('And it has easier chords!');
            }
        }

        if (session('user')->isLoggedIn()) {
            $feedback = $feedbackRepository->readSongFeedbackForUser(
                session('user')->id_user ?? null,
                $transposedSong->song->idSong
            );
        }

        /** @deprecated  */
        if (str_starts_with($req->headers->get('Accept'), 'application/json')) {
            return (new TransposeSongApi($app))->handleApiRequest($req, $id_song);
        }

        return response()->view(
            'transpose_song',
            array_merge(
                $tplVars, [
                    'song'             => $transposedSong,
                    'your_voice'       => $your_voice,
                    'voice_chart'      => $transpositionChart->getChartHtml(),
                    'page_title'       => __(
                        ':song (Neocatechumenal Way)',
                        ['song' => $transposedSong->song->title]
                    ),
                    'header_link'      => route('book_' . $transposedSong->song->idBook),
                    'meta_canonical'   => route('transpose_song', ['id_song' => $transposedSong->song->slug]),
                    'meta_description' => __(
                        'Transpose the chords of &quot;:song&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
                        ['song' => $transposedSong->song->title]
                    ),
                    'page_class'       => 'transpose-song',
                    'feedback'         => $feedback ?? null,
                    'all_books'	       => $bookRepository->readAllBooks(),

                    'user_less_than_one_octave' => $nc->rangeWideness(session('user')->range) < 12,
                    'url_wizard'                => route('wizard_step1', ['locale' => App::getLocale()]),

                    //Non-JS browsers show message after clicking on feedback
                    'non_js_fb'                 => $req->get('fb')
                ]
            )
        );
    }

    private function generateTranspositionChart(NotesCalculator $nc, TransposedSong $transposedSong) : TranspositionChart
    {
        $transpositionChart = new TranspositionChart($nc, $transposedSong->song, session('user'), config('nt.languages')[App::getLocale()]['notation']);
        $transpositionChart->addTransposition('Transposed:', 'transposed-song', $transposedSong->transpositions[0]);

        if ($transposedSong->song->peopleRange !== null) {
            $transpositionChart->addVoice('Original for people:', 'original-song original-people', $transposedSong->song->peopleRange);
            $transpositionChart->addVoice('Transposed for people:', 'transposed-song transposed-people', $transposedSong->transpositions[0]->peopleRange);
            $transpositionChart->addVoice('People standard:', 'people-standard', new NotesRange(config('nt.people_range')[0], config('nt.people_range')[1]));
        }

        if ($transposedSong->getPeopleCompatible() !== null) {
            $transpositionChart->addTransposition('Adjusted for you:', 'transposed-song', $transposedSong->getPeopleCompatible());
            $transpositionChart->addVoice('Adjusted for people:', 'people-compatible', $transposedSong->getPeopleCompatible()->peopleRange);
        }

        return $transpositionChart;
    }
}
