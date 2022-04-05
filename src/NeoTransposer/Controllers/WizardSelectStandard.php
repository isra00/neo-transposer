<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * First step of the Wizard: choose a pre-defined voice range. In the next step
 * (WizardEmpiric) that voice range will be refined through empirical tests so
 * the real voice range can be measured.
 */
class WizardSelectStandard
{
    public function showPage(NeoApp $app): string
    {
        return $app->render('wizard_select_standard.twig', array(
            'page_title' => $app->trans('Voice measure wizard')
        ));
    }

    /**
     * This is also a GET request
     *
     * @param Request $req
     * @param NeoApp  $app
     *
     * @return RedirectResponse
     */
    public function selectStandard(Request $req, NeoApp $app): RedirectResponse
    {
        $standard_voices = $app['neoconfig']['voice_wizard']['standard_voices'];

        //Invalid voice gender => go back
        if (!in_array($req->get('gender'), array_keys($standard_voices))) {
            return $app->redirect($app->path('wizard_step1'));
        }

        $app['neouser']->range = new NotesRange(
            $standard_voices[$req->get('gender')][0],
            $standard_voices[$req->get('gender')][1]
        );

        $app['neouser']->wizard_step1 = $req->get('gender');

        return $app->redirect($app->path('wizard_empiric_lowest') . '#instructions');
    }
}
