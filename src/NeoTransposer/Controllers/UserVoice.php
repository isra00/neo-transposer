<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\NotesNotation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Page for the user to set his/her voice range, or to go to the Wizard.
 */
class UserVoice
{
    public function get(Request $request, \NeoTransposer\NeoApp $app): string
    {
        if ($request->get('bad_voice_range')) {
            $app->addNotification(
                'error',
                $app->trans(
                    'Are you sure that is your real voice range? If you don\'t know, you can use the assistant to measure it.'
                )
            );
        }

        return $app->render('user_voice.twig', array(
            'page_title'           => $app->trans('Your voice'),
            'scale'                => (new NotesCalculator())->numbered_scale,
            'accoustic_scale'      => NotesCalculator::ACOUSTIC_SCALE,
            'accoustic_scale_nice' => (new NotesNotation())->getNotationArray(
                NotesCalculator::ACOUSTIC_SCALE,
                $app['neoconfig']['languages'][$app['locale']]['notation']
            ),
            //First usage: if user manually selects range, they will be redirected to the book in their language
            'redirect'             => $request->get('redirect') ?? $app->path('book_' . $app['neouser']->id_book)
        ));
    }
}
