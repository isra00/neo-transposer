<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Exception\SongNotExistException;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\PeopleCompatibleCalculation;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\TranspositionChart;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\Request;

/**
 * Transpose Song page: transpose the given song for the singer's voice range.
 */
class TransposeSong
{
    public function get(NeoApp $app, Request $req, $id_song)
    {
        $transposedSong = null;
        //For the teaser (not logged in), transpose for a standard male voice
        if (!$app['neouser']->isLoggedIn()) {
            $app['neouser']->range = new NotesRange('B1', 'F#3');
        }
        //If null user, redirect to User Settings
        elseif (empty($app['neouser']->range->lowest)) {
            $app->setLocaleAutodetect($req);

            return $app->redirect(
                $app->path(
                    'user_voice',
                    ['_locale' => $app['locale']]
                )
            );
        }

        try {
            $transposedSong = TransposedSong::fromDb($id_song, $app);
        } catch (SongNotExistException) {
            $app->abort(404, "Song $id_song does not exist.");
        }

        $transposedSong->transpose($app['neouser']->range);

        $app['locale'] = $transposedSong->song->bookLocale;
        $app['translator']->setLocale($app['locale']);

        $your_voice = $app['neouser']->getVoiceAsString(
            $app['translator'],
            new NotesNotation(),
            $app['neoconfig']['languages'][$app['locale']]['notation']
        );

        $nc = new NotesCalculator();

        $transpositionChart = $this->generateTranspositionChart($nc, $app, $transposedSong);

        $tplVars = [];

        if ($transposedSong->getPeopleCompatible() !== null) {

            $peopleCompatibleMsgUntranslated = '';
            switch ($transposedSong->getPeopleCompatibleStatus())
            {
                case PeopleCompatibleCalculation::ADJUSTED_WELL:
                    $peopleCompatibleMsgUntranslated = 'This other transposition, though a bit %difference%, fits well the people of the assembly.';
                    $tplVars['peopleCompatibleClass'] = 'star';
                    break;
                case PeopleCompatibleCalculation::ADJUSTED_WIDER:
                    $peopleCompatibleMsgUntranslated = 'This other transposition, though a bit %difference%, may probably fit better the people of the assembly.';
                    break;
                case PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE:
                    $peopleCompatibleMsgUntranslated = 'The chords given above are good for your voice, but probably too high for the assembly. The following transposition is %difference%, though still high for some people of the assembly.';
                    break;
                case PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE:
                    $peopleCompatibleMsgUntranslated = 'The chords given above are good for your voice, but probably too low for the assembly. The following transposition is %difference%, though still low for some people of the assembly.';
                    break;
            }

            $tplVars['peopleCompatibleMsg'] = $app->trans(
                $peopleCompatibleMsgUntranslated,
                [
                    '%difference%' => ($transposedSong->getPeopleCompatible()->deviationFromCentered > 0)
                        ? $app->trans('higher')
                        : $app->trans('lower')
                ]
            );

            if ($transposedSong->getPeopleCompatible()->score < $transposedSong->transpositions[0]->score) {
                $tplVars['peopleCompatibleMsg'] .= ' ' . $app->trans('And it has easier chords!');
            }
        }

        if ($app['neouser']->isLoggedIn()) {
            $feedback = $app[FeedbackRepository::class]->readSongFeedbackForUser(
                $app['neouser']->id_user ?? null,
                $transposedSong->song->idSong
            );
        }

        if (str_starts_with($req->headers->get('Accept'), 'application/json')) {
            return (new TransposeSongApi($app))->handleApiRequest($req, $id_song);
        }

        return $app->render(
            'transpose_song.twig',
            array_merge(
                $tplVars, [
                    'song'             => $transposedSong,
                    'your_voice'       => $your_voice,
                    'voice_chart'      => $transpositionChart->getChartHtml(),
                    'page_title'       => $app->trans(
                        '%song% (Neocatechumenal Way)',
                        ['%song%' => $transposedSong->song->title]
                    ),
                    'header_link'      => $app->path('book_' . $transposedSong->song->idBook),
                    'meta_canonical'   => $app->url('transpose_song', ['id_song' => $transposedSong->song->slug]),
                    'meta_description' => $app->trans(
                        'Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
                        ['%song%' => $transposedSong->song->title]
                    ),
                    'feedback'         => $feedback ?? null,

                    'user_less_than_one_octave' => $nc->rangeWideness($app['neouser']->range) < 12,
                    'url_wizard'                => $app->path('wizard_step1', ['_locale' => $app['locale']]),

                    //Non-JS browsers show message after clicking on feedback
                    'non_js_fb'                 => $req->get('fb')
                ]
            )
        );
    }

    protected function generateTranspositionChart(NotesCalculator $nc, NeoApp $app, TransposedSong $transposedSong) : TranspositionChart
    {
        $transpositionChart = new TranspositionChart($nc, $transposedSong->song, $app['neouser'], $app['neoconfig']['languages'][$app['locale']]['notation']);
        $transpositionChart->addTransposition('Transposed:', 'transposed-song', $transposedSong->transpositions[0]);

        if ($transposedSong->song->peopleRange !== null) {
            $transpositionChart->addVoice('Original for people:', 'original-song original-people', $transposedSong->song->peopleRange);
            $transpositionChart->addVoice('Transposed for people:', 'transposed-song transposed-people', $transposedSong->transpositions[0]->peopleRange);
            $transpositionChart->addVoice('People standard:', 'people-standard', new NotesRange($app['neoconfig']['people_range'][0], $app['neoconfig']['people_range'][1]));
        }

        if ($transposedSong->getPeopleCompatible() !== null) {
            $transpositionChart->addTransposition('Adjusted for you:', 'transposed-song', $transposedSong->getPeopleCompatible());
            $transpositionChart->addVoice('Adjusted for people:', 'people-compatible', $transposedSong->getPeopleCompatible()->peopleRange);
        }

        return $transpositionChart;
    }
}
