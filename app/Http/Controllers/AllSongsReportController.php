<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use NeoTransposer\Domain\AllSongsReport;
use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\Repository\BookRepository;

final class AllSongsReportController extends Controller
{
    public function get(Request $req, AllSongsReport $allSongsReport, BookRepository $bookRepository)
    {
        $user = session('user');
        $locale = App::getLocale();
        $idBook = $bookRepository->readIdBookFromLocale($locale);

        $allSongsTransposedWithFeedback = $allSongsReport->getAllTranspositions($idBook, $user);

        $your_voice = $user->getVoiceAsString(
            new NotesNotation(),
            config('nt.languages')[$locale]['notation']
        );

        $tplVars = [
            'all_songs_transposed_with_fb' => $allSongsTransposedWithFeedback,
            'your_voice'    => $your_voice,
            'header_link'   => route('book_' . $idBook),
            'page_title'    => __('All transpositions for your voice'),
            'page_class'    => 'all-songs-report',
            'print_css_code' => null,
        ];

        if ($req->get('dl')) {
            $tplVars['print_css_code'] = file_get_contents(public_path('static/style.css'))
                . file_get_contents(public_path('static/print.css'));

            $tplVars['header_link'] = url('/');
        }

        $responseBody = response()->view('all_songs_report', $tplVars)->getContent();

        if (!$req->get('dl')) {
            return response($responseBody);
        }

        $filename = __('Transpositions')
            . '-' . str_replace('#', 'd', $user->range->lowest . '-' . $user->range->highest)
            . '.html';

        return response($responseBody, 200, [
            'Cache-Control'       => 'private',
            'Content-Type'        => 'application/stream',
            'Content-Length'      => strlen($responseBody),
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }
}
