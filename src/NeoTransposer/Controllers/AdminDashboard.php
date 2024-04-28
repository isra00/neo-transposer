<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

/**
 * Administrator's dashboard.
 */
final class AdminDashboard
{
	public function get(Request $req, \NeoTransposer\NeoApp $app): string
	{
		$app['locale'] = 'es';

		$toolOutput = '';

		if ($tool = $req->get('tool'))
		{
            $allTools = [
                'PopulateUsersCountry',
                'CheckSongsRangeConsistency',
                'CheckUsersRangeConsistency',
                'RefreshCompiledCss',
                'RemoveOldCompiledCss',
                'CheckChordsOrder',
                'TestAllTranspositions',
                'GetVoiceRangeOfGoodUsers',
                'CheckOrphanChords',
                'GetPerformanceByNumberOfFeedbacks',
                'CheckMissingTranslations'
            ];

            if (!in_array($tool, $allTools)) {
                $app->abort(404, "Invalid tool name $tool");
            }

            try {
                $toolObject = $app["NeoTransposer\\Domain\\AdminTasks\\$tool"];
            } catch (\Pimple\Exception\UnknownIdentifierException)
            {
                $app->abort(404, 'Invalid admin tool');
            }
            return $toolObject->run();
		}

        $readMetricsUseCase = $app[\NeoTransposer\Domain\Service\AdminMetricsReader::class];
        $metricsFromService = $readMetricsUseCase->readAdminMetrics(!empty($req->get('long')));

		return $app->render('admin_dashboard.twig', $metricsFromService + [
			'page_title'			=> 'Dashboard · ' . $app['neoconfig']['software_name'],
			'tool_output'			=> $toolOutput,
        ], false);
	}
}
