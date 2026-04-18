<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\AdminTasks\AdminTask;
use NeoTransposer\Domain\Service\AdminMetricsReader;

final class AdminDashboardController extends Controller
{
    private const TOOLS = [
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
        'CheckMissingTranslations',
    ];

    public function get(Request $request, AdminMetricsReader $adminMetricsReader)
    {
        app()->setLocale('es');

        if ($tool = $request->get('tool')) {
            if (!in_array($tool, self::TOOLS)) {
                abort(404, "Invalid tool name $tool");
            }

            $class = "NeoTransposer\\Domain\\AdminTasks\\$tool";
            /** @var AdminTask $taskObject */
            $taskObject = app()->make($class);
            return response($taskObject->run());
        }

        $metrics = $adminMetricsReader->readAdminMetrics(!empty($request->get('long')));

        return response()->view('admin_dashboard', $metrics + [
            'page_title' => 'Dashboard · ' . config('nt.software_name'),
            'page_class' => 'admin-dashboard',
        ]);
    }
}
