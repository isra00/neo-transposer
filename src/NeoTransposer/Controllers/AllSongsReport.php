<?php

namespace NeoTransposer\Controllers;

use \Symfony\Component\HttpFoundation\Response;

/**
 * Transpose Song page: transposes the given song for the singer's voice range.
 */
class AllSongsReport
{
	/**
	 * HTML report.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp
	 * @return string The rendered view (HTML).
	 */
	public function get(\NeoTransposer\NeoApp $app)
	{
		$reportModel = new \NeoTransposer\Model\AllSongsReport($app);
		$allTranspositions = $reportModel->getAllTranspositions();

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		return $app->render('all_songs_report.twig', array(
			'songs'			=> $allTranspositions,
			'your_voice'	=> $your_voice,
			'header_link' 	=> $app['url_generator']->generate('book_' . $allTranspositions[0]->song_details['id_book']),
			'page_title'  	=> $app->trans('All transpositions for your voice'),
		));
	}

	/**
	 * PDF report.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp
	 * 
	 * @return Response A redirection to the PDF (they are all served statically).
	 */
	public function getPdf(\NeoTransposer\NeoApp $app)
	{
		$reportModel = new \NeoTransposer\Model\AllSongsReport($app);
		$pdfReportUrl = $reportModel->getPdfReportUrl();

		return $app->redirect($pdfReportUrl);
	}
}