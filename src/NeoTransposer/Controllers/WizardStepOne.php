<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

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
		return $app->render('wizard_step1.tpl');
	}

	public function postStepOne(Request $req, NeoApp $app)
	{
		$standard_voices = $app['neoconfig']['standard_voices'];

		//Invalid POST data => go back
		if (false === array_search($req->get('gender'), array_keys($standard_voices)))
		{
			return $this->getStepOne($app);
		}

		$app['user']->lowest_note = $standard_voices[$req->get('gender')][0];
		$app['user']->highest_note = $standard_voices[$req->get('gender')][1];

		return $app->redirect($app['url_generator']->generate('wizard_empiric_lowest'));
	}
}