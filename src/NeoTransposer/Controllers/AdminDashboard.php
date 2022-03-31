<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\AdminMetricsReader;
use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use NeoTransposer\Model\UnhappyUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administrator's dashboard.
 */
class AdminDashboard
{

	/**
	 * The App in use, for easier access.
	 * @var \NeoTransposer\NeoApp
	 */
	protected $app;

    /**
     * @var array
     */
    protected $countryNames;

	public function get(Request $req, \NeoTransposer\NeoApp $app): string
	{
		$app['locale'] 	= 'es';
		$this->app 		= $app;

		$toolOutput 	= '';

		if ($tool = $req->get('tool'))
		{
			$toolsMethods = [
				'populateCountry', 
				'checkLowerHigherNotes', 
				'refreshCss',
				'removeOldCompiledCss',
				'testAllTranspositions',
				'getVoiceRangeOfGoodUsers',
				'detectOrphanChords',
				'checkChordOrderHumanFriendly',
				'checkUserLowerHigherNotes',
				'getPerformanceByNumberOfFeedbacks',
				'diffTranslations'
			];

			if (!in_array($tool, $toolsMethods))
			{
				$app->abort(404);
			}

			$tools 		= new \NeoTransposer\Model\AdminTools($app);
			$toolOutput = $tools->{$tool}();
		}

        $readMetricsUseCase = $app[\NeoTransposer\Application\ReadAdminMetrics::class];
        $metricsFromService = $readMetricsUseCase->readAdminMetrics(!empty($req->get('long')));

		return $app->render('admin_dashboard.twig', $metricsFromService + [
			'page_title'			=> 'Dashboard · ' . $app['neoconfig']['software_name'],
			'tool_output'			=> $toolOutput,
        ], false);
	}

}
