<?php

namespace NeoTransposer\Controllers;

use MatthiasMullie\Minify;

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
		$minifier		= new Minify\CSS($app['root_dir'] . '/web' . $this->src_file);
		$minified_css 	= $minifier->minify();
		$minified_hash 	= md5($minified_css);

		file_put_contents(
			$app['root_dir'] . '/web' . sprintf($this->min_file, $minified_hash),
			$minified_css
		);

		$config_file 	= $app['root_dir'] . '/config.php';

		$config_src 	= file_get_contents($config_file);
		$config_src 	= preg_replace(
			"/(\s*'css_cache'\s*=>\s*')([a-f\d]{32})(',\s*)/", 
			"\${1}$minified_hash\${3}", 
			$config_src
		);
		file_put_contents($config_file, $config_src);

		return $app->redirect(sprintf($this->min_file, $minified_hash));
	}
}
