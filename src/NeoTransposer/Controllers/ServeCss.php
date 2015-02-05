<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

class ServeCss
{
	protected $src_file = '/static/style.css';
	protected $min_file = '/static/style.min.css';

	/**
	 * This mechanism for minimizing CSS takes advantage of the RewriteRule:
	 * if the file style.min.css does not exist, this controller will be called.
	 * After the first request, the static file will be served directly by Apache.
	 * THE MINIFIED FILE MUST BE MANUALLY REMOVED AFTER EVERY UPDATE.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The Silex app.
	 */
	public function get(\NeoTransposer\NeoApp $app)
	{
		$source_css = file_get_contents($app['root_dir'] . '/web' . $this->src_file);

		$fields_string = 'input=' . $source_css;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, 'http://cssminifier.com/raw');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

		$min_css = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// If external service fails, store the original.
		if ('200' != $http_status)
		{
			$min_css = $source_css;
		}

		file_put_contents(
			$app['root_dir'] . '/web' . $this->min_file,
			$min_css
		);

		return $app->redirect($this->min_file);
	}
}