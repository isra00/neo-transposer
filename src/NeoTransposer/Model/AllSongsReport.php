<?php

namespace NeoTransposer\Model;

class AllSongsReport
{
	/**
	 * App instance
	 * @var \NeoTransposer\NeoApp
	 */
	protected $app;

	public function __construct(\NeoTransposer\NeoApp $app)
	{
		$this->app = $app;
	}

	/**
	 * Return the report trying to fetch it from cache - or generating it.
	 * 
	 * @return string The report public URL.
	 */
	public function getPdfReportUrl()
	{
		if (!file_exists($this->getPdfCacheStorePath()))
		{
			$this->generatePdfReport();
		}

		$reportUrl = $this->app['absoluteBasePath']
		 . '/' . $this->app['neoconfig']['pdf_reports_dir']
		 . '/' . $this->getPdfCacheKey();

		return $reportUrl;
	}

	/**
	 * Generate and store the report.
	 *
	 * The PDF is generated with DomPDF after rendering a special view.
	 * 
	 * @return string The filename of the generated report.
	 */
	public function generatePdfReport()
	{
		$your_voice = $this->app['neouser']->getVoiceAsString(
			$this->app['translator'],
			$this->app['neoconfig']['languages'][$this->app['locale']]['notation']
		);

		$reportHtml = $this->app->render('all_songs_report_pdf.twig', array(
			'songs' 		=> $this->getAllTranspositions(),
			'your_voice'	=> str_replace('&rarr;', '-', $your_voice),
		));

		define('DOMPDF_ENABLE_AUTOLOAD', false);

		require_once $this->app['root_dir'] . '/vendor/dompdf/dompdf/dompdf_config.inc.php';

		$dompdf = new \DOMPDF();
		$dompdf->load_html($reportHtml);
		$dompdf->render();

		return file_put_contents($this->getPdfCacheStorePath(), $dompdf->output());
	}

	/**
	 * Generate cache key for the report
	 * 
	 * @return string A .pdf filename with locale and user's lowest and highest notes.
	 */
	public function getPdfCacheKey()
	{
		return $this->app['locale']
		 . '-' . str_replace('#', 'd', $this->app['neouser']->lowest_note)
		 . '-' . str_replace('#', 'd', $this->app['neouser']->highest_note)
		 . '.pdf';
	}

	/**
	 * Generate the local path for the cached file.
	 * 
	 * @return string Absolute local (filesystem) path.
	 */
	public function getPdfCacheStorePath()
	{
		return $this->app['root_dir']
		 . '/web/'
		 . $this->app['neoconfig']['pdf_reports_dir']
		 . '/' . $this->getPdfCacheKey();
	}
	
	/**
	 * Fetches all the songs from the current book and transposes them.
	 * 
	 * @return array Array of TransposedSong objects.
	 */
	public function getAllTranspositions()
	{

		$sql = <<<SQL
SELECT id_song
FROM song 
JOIN book USING (id_book) 
WHERE locale = ? 
AND NOT song.id_song = 118
AND NOT song.id_song = 319
ORDER BY page
SQL;

		$ids = $this->app['db']->fetchAll($sql, array($this->app['locale']));

		$songs = array();

		foreach ($ids as $id)
		{
			$song = TransposedSong::create($id['id_song'], $this->app);
			$song->transpose();

			//Remove bracketed text from song title (used for aclarations)
			$song->song->title = preg_replace('/(.)\[.*\]/', '$1', $song->song->title);

			$songs[] = $song;
		}

		return $songs;
	}
}