<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Application\AdminTaskNotExistException;
use NeoTransposer\Application\RunAdminTool;
use NeoTransposer\Domain\AdminMetricsReader;
use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use NeoTransposer\Model\UnhappyUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administrator's dashboard.
 */
class AdminDashboard
{
	public function get(Request $req, \NeoTransposer\NeoApp $app): string
	{
		$app['locale'] = 'es';

		$toolOutput = '';

		if ($tool = $req->get('tool'))
		{
            $adminToolRunner = new RunAdminTool($app);

            try {
                $toolOutput = $adminToolRunner->runAdminTask($tool);
            } catch (AdminTaskNotExistException $e)
            {
                $app->abort(404, $e->getMessage());
            }
		}

        $readMetricsUseCase = $app[\NeoTransposer\Application\ReadAdminMetrics::class];
        $metricsFromService = $readMetricsUseCase->readAdminMetrics(!empty($req->get('long')));

		return $app->render('admin_dashboard.twig', $metricsFromService + [
			'page_title'			=> 'Dashboard · ' . $app['neoconfig']['software_name'],
			'tool_output'			=> $toolOutput,
        ], false);
	}
}
