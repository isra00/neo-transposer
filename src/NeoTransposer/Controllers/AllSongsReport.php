<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use NeoTransposer\Model\{TransposedSong, PeopleCompatibleCalculation, TransposedSongFactory};

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
class AllSongsReport
{
    public $peopleCompatibleMicroMessages = [
    PeopleCompatibleCalculation::ALREADY_COMPATIBLE     => '',
    PeopleCompatibleCalculation::WIDER_THAN_SINGER         => '',
    PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE     => '',
    PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE     => '',
    PeopleCompatibleCalculation::ADJUSTED_WELL             => ' ★',
    PeopleCompatibleCalculation::ADJUSTED_WIDER         => ' ☆',
    PeopleCompatibleCalculation::NOT_ADJUSTED_WIDER     => '',
    PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA     => '',
    ];

    /**
     * HTML report. If dl query string arg is present, the page is offered to
     * download, included the styles inside the HTML.
     * 
     * @param  \NeoTransposer\NeoApp $app The NeoApp
     * @return string The rendered view (HTML).
     */
    public function get(\NeoTransposer\NeoApp $app, Request $req)
    {
        $allTranspositions = $this->getAllTranspositions($app);

        $your_voice = $app['neouser']->getVoiceAsString(
            $app['translator'],
            $app['neoconfig']['languages'][$app['locale']]['notation']
        );

        $tplVars = array(
        'songs'            => $allTranspositions,
        'your_voice'    => $your_voice,
        'header_link'     => $app->path('book_' . $allTranspositions[0]->song->idBook),
        'page_title'      => $app->trans('All transpositions for your voice'),
        );

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
            $responseBody, 200, array(
            'Cache-Control'         => 'private',
            'Content-Type'             => 'application/stream',
            'Content-Length'         => strlen($responseBody),
            'Content-Disposition'     => 'attachment; filename=' . $filename,
            )
        );
    }

    /**
     * Fetches all the songs from the current book and transposes them.
     * 
     * @return array Array of TransposedSong objects.
     */
    public function getAllTranspositions(\NeoTransposer\NeoApp $app)
    {

        $sql = <<<SQL
SELECT song.id_song, page, title, transposition_feedback.worked, transposition_feedback.transposition
FROM song 
JOIN book USING (id_book)
LEFT JOIN transposition_feedback
	ON transposition_feedback.id_song = song.id_song
	AND transposition_feedback.id_user = ?
WHERE
	locale = ?
	AND NOT song.id_song IN (118, 319)
ORDER BY page, title
SQL;

        $ids = $app['db']->fetchAll($sql, [$app['neouser']->id_user, $app['locale']]);

        $songs = [];

        $transposedSongFactory = new TransposedSongFactory($app);

        foreach ($ids as $id)
        {
            /**
             * @refactor Hacer una sola query para todos los cantos (si acaso
             *           una adicional para los acordes), e instanciar
             *           TransposedSong con el constructor, no con el
             *           createTransposedSongFromSongId().
             */
            $song = $transposedSongFactory->createTransposedSongFromSongId($id['id_song']);

            $song->transpose();

            /**
             * @refactor Insertar atributos públicos en runtime no es muy SOLID...
             *           Solución: usar una clase wrapper TransposedSongInReport
             */
            $song->peopleCompatibleStatusMicroMsg = $this->peopleCompatibleMicroMessages[$song->getPeopleCompatibleStatus()];
            $song->feedbackWorked = $id['worked'];
            $song->feedbackTransposition = $id['transposition'];

            /** @see https://github.com/isra00/neo-transposer/issues/129#issuecomment-867496701 */
            if ("peopleCompatible" == $song->feedbackTransposition && empty($song->peopleCompatible)) {
                $song->feedbackTransposition = "centered1";
            }

            //Remove bracketed text from song title (used for clarifications)
            /** @todo Remove this: bracketed text differentiates variants! */
            $song->song->title = preg_replace('/(.)\[.*\]/', '$1', $song->song->title);

            $songs[] = $song;
        }

        return $songs;
    }
}
