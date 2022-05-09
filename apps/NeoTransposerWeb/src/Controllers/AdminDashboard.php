<?php

namespace NeoTransposerWeb\Controllers;

use NeoTransposerApp\Application\AdminTaskNotExistException;
use NeoTransposerApp\Application\RunAdminTool;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administrator's dashboard.
 */
class AdminDashboard
{
	public function get(Request $req, \NeoTransposerWeb\NeoApp $app): string
	{
		$app['locale'] = 'es';

		$toolOutput = '';

		if ($task = $req->get('tool'))
		{
            $runAdminTaskUseCase = new RunAdminTool($app);

            try {
                $toolOutput = $runAdminTaskUseCase->runAdminTask($task);
            } catch (AdminTaskNotExistException $e)
            {
                $app->abort(404, $e->getMessage());
            }
		}

        $readMetricsUseCase = $app[\NeoTransposerApp\Application\ReadAdminMetrics::class];
        $metricsFromService = $readMetricsUseCase->readAdminMetrics(!empty($req->get('long')));

		return $app->render('admin_dashboard.twig', $metricsFromService + [
			'page_title'			=> 'Dashboard · ' . $app['neoconfig']['software_name'],
			'tool_output'			=> $toolOutput,
        ], false);
	}
}
