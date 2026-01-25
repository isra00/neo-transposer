<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NeoTransposer\Domain\Service\FeedbackRecorder;

/**
 * AJAX Controller to receive/record user feedback. This URL has NO CONTENT.
 */
class ReceiveFeedbackController extends Controller
{
    public function post(Request $request, FeedbackRecorder $feedbackRecorder): JsonResponse|RedirectResponse
    {
        $user = session('user');

        // This usually happens when the session times out (=> HTTP status 408).
        if (!$user || !$user->isLoggedIn()) {
            // The JSON body is superfluous since JS reads the status code only.
            return $request->ajax()
                ? response()->json([], 408)
                : redirect()->back();
        }

        if (empty($request->get('id_song')) || null === $request->get('worked')) {
            return response()->json(['error' => 'Parameters id_song and worked are mandatory'], 400);
        }

        $feedbackRecorder->recordFeedback(
            $user,
            (int) $request->get('id_song'),
            (bool) $request->get('worked'),
            $user->range,
            $request->get('pc_status'),
            (float) $request->get('centered_score_rate'),
            $request->get('deviation') ? (int) $request->get('deviation') : null,
            $request->get('transposition')
        );

        // Progressive enhancement: support form submission without AJAX, then refresh the page.
        if (!$request->ajax()) {
            $feedbackParam = str_replace(['1', '0'], ['yes', 'no'], (string) (int) $request->get('worked'));
            return redirect(
                route('transpose_song', ['id_song' => $request->get('id_song')]) . '?fb=' . $feedbackParam . '#feedback'
            );
        }

        return response()->json(['feedback' => 'received'], 200);
    }
}
