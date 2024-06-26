<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\NeoApp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
final class AllSongsReport
{
    /**
     * HTML report. If dl query string arg is present, the page is offered to
     * download, included the styles inside the HTML.
     *
     * @param  NeoApp $app The NeoApp
     * @return string|Response The rendered view (HTML) or the downloadable HTML.
     */
    public function get(NeoApp $app, Request $req): string|\Symfony\Component\HttpFoundation\Response
    {
        $idBook = $app[\NeoTransposer\Domain\Repository\BookRepository::class]->readIdBookFromLocale($app['locale']);

        $allSongsReport = $app[\NeoTransposer\Domain\AllSongsReport::class];

        $allSongsTransposedWithFeedback = $allSongsReport->getAllTranspositions(
            $idBook,
            $app['neouser']
        );

        $your_voice = $app['neouser']->getVoiceAsString(
            $app['translator'],
            new NotesNotation(),
            $app['neoconfig']['languages'][$app['locale']]['notation']
        );

        $tplVars = [
            'all_songs_transposed_with_fb' => $allSongsTransposedWithFeedback,
            'your_voice'  => $your_voice,
            'header_link' => $app->path('book_' . $idBook),
            'page_title'  => $app->trans('All transpositions for your voice'),
        ];

        if ($req->get('dl')) {
            $tplVars['print_css_code'] = file_get_contents($app['root_dir'] . '/web/static/style.css')
            . file_get_contents($app['root_dir'] . '/web/static/print.css');

            $tplVars['header_link'] = $app['absoluteBasePath'];
        }

        $tpl = $req->get('dev') ? 'all_songs_report_dev' : 'all_songs_report';
        $responseBody = $app->render("$tpl.twig", $tplVars);

        if (!$req->get('dl')) {
            return $responseBody;
        }

        $filename = $app->trans('Transpositions')
        . '-' . str_replace('#', 'd', $app['neouser']->range->lowest . '-' . $app['neouser']->range->highest)
        . '.html';

        return new Response(
            $responseBody, 200, [
                'Cache-Control'       => 'private',
                'Content-Type'        => 'application/stream',
                'Content-Length'      => strlen($responseBody),
                'Content-Disposition' => 'attachment; filename=' . $filename,
            ]
        );
    }
}
