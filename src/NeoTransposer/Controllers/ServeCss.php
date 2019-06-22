<?php

namespace NeoTransposer\Controllers;

/**
 * Controller that serves merged+compressed CSS files for the app.
 */
class ServeCss
{
	protected $src_file = '/static/style.css';
	public $min_file = '/static/compiled-%s.css';

	/**
	 * This mechanism for minimizing CSS takes advantage of the RewriteRule:
	 * if the minified CSS file does not exist, this controller will be called.
	 * After the first request, the static file will be served directly by Apache.
	 * THE MINIFIED FILE MUST BE MANUALLY REMOVED AFTER EVERY UPDATE (in AdminTools)
	 * 
	 * @param  \NeoTransposer\NeoApp $app The Silex app.
	 */
	public function get(\NeoTransposer\NeoApp $app)
	{
		$source_css = file_get_contents($app['root_dir'] . '/web' . $this->src_file);

		$fields_string = 'input=' . $source_css;

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, 'https://cssminifier.com/raw');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);

		$min_css = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		// If external service fails, store the original.
		if ('200' != $http_status)
		{
			$min_css = $source_css;
		}

		$minified_hash = md5($min_css);

		file_put_contents(
			$app['root_dir'] . '/web' . sprintf($this->min_file, $minified_hash),
			$min_css
		);

		$config_file = $app['root_dir'] . '/config.php';

		$config_src = file_get_contents($config_file);
		$config_src = preg_replace(
			"/(\s*'css_cache'\s*=>\s*')([a-f\d]{32})(',\s*)/", 
			"\${1}$minified_hash\${3}", 
			$config_src
		);
		file_put_contents($config_file, $config_src);

		return $app->redirect(sprintf($this->min_file, $minified_hash));
	}
}
