<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

/**
 * First step of the Wizard: choose a pre-defined voice range. In the next step
 * (WizardEmpiric) that voice range will be refined through empirical tests so
 * the real voice range can be measured.
 */
class WizardStepOne
{
	public function stepOne(Request $req, NeoApp $app)
	{
		if ('GET' == $req->getMethod())
		{
			return $this->getStepOne($app);
		}

		return $this->postStepOne($req, $app);
	}

	public function getStepOne(NeoApp $app)
	{
		return $app->render('wizard_step1.twig', array(
			'page_title' => $app->trans('Voice measure wizard')
		));
	}

	public function postStepOne(Request $req, NeoApp $app)
	{
		$standard_voices = $app['neoconfig']['voice_wizard']['standard_voices'];

		//Invalid POST data => go back
		if (false === array_search($req->get('gender'), array_keys($standard_voices)))
		{
			return $this->getStepOne($app);
		}

		$app['neouser']->lowest_note = $standard_voices[$req->get('gender')][0];
		$app['neouser']->highest_note = $standard_voices[$req->get('gender')][1];

		$app['neouser']->wizard_step1 = $req->get('gender');

		return $app->redirect($app['url_generator']->generate('wizard_empiric_lowest') . '#instructions');
	}
}