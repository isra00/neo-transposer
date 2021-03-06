<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;
use \NeoTransposer\Model\NotesRange;

/**
 * First step of the Wizard: choose a pre-defined voice range. In the next step
 * (WizardEmpiric) that voice range will be refined through empirical tests so
 * the real voice range can be measured.
 */
class WizardStepOne
{
	public function stepOne(Request $req, NeoApp $app)
	{
		if (empty($req->get('gender')))
		{
			return $this->beforeClick($app);
		}

		return $this->afterClick($req, $app);
	}

	public function beforeClick(NeoApp $app)
	{
		return $app->render('wizard_step1.twig', array(
			'page_title' => $app->trans('Voice measure wizard')
		));
	}

	public function afterClick(Request $req, NeoApp $app)
	{
		$standard_voices = $app['neoconfig']['voice_wizard']['standard_voices'];

		//Invalid voice gender => go back
		if (false === array_search($req->get('gender'), array_keys($standard_voices)))
		{
			return $app->redirect($app->path('wizard_step1'));
		}

		if (empty($app['neouser']->range))
		{
			$app['neouser']->range = new NotesRange;
		}

		$app['neouser']->range->lowest  = $standard_voices[$req->get('gender')][0];
		$app['neouser']->range->highest = $standard_voices[$req->get('gender')][1];

		$app['neouser']->wizard_step1 = $req->get('gender');

		return $app->redirect($app->path('wizard_empiric_lowest') . '#instructions');
	}
}
