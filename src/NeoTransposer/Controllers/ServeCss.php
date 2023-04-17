<?php

namespace NeoTransposer\Controllers;

/**
 * Controller that serves merged+compressed CSS files for the app.
 */
final class ServeCss
{
	private const SRC_FILE = '/static/style.css';
	public $min_file = '/static/compiled-%s.css';

	/**
	 * This mechanism for minimizing CSS takes advantage of the RewriteRule:
	 * if the minified CSS file does not exist, this controller will be called.
	 * After the first request, the static file will be served directly by Apache.
	 * THE MINIFIED FILE MUST BE MANUALLY REMOVED AFTER EVERY UPDATE (in AdminTools)
	 *
	 * @param \NeoTransposer\NeoApp $app The Silex app.
	 */
	public function get(\NeoTransposer\NeoApp $app): \Symfony\Component\HttpFoundation\RedirectResponse
	{
		$minified_css = $this->minify_css(file_get_contents($app['root_dir'] . '/web' . self::SRC_FILE));
		$minified_hash 	= md5((string) $minified_css);

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

	/**
	 * @url https://gist.github.com/Rodrigo54/93169db48194d470188f
	 *
	 * @param string $input
	 */
	function minify_css($input) {
		if(trim($input) === "") return $input;
		return preg_replace(
			[
				// Remove comment(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
				// Remove unused white-space(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
				// Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
				'#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
				// Replace `:0 0 0 0` with `:0`
				'#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
				// Replace `background-position:0` with `background-position:0 0`
				'#(background-position):0(?=[;\}])#si',
				// Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
				'#(?<=[\s:,\-])0+\.(\d+)#s',
				// Minify string value
				'#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
				'#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
				// Minify HEX color code
				'#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
				// Replace `(border|outline):none` with `(border|outline):0`
				'#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
				// Remove empty selector(s)
				'#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ],
			[
				'$1',
				'$1$2$3$4$5$6$7',
				'$1',
				':0',
				'$1:0 0',
				'.$1',
				'$1$3',
				'$1$2$4$5',
				'$1$2$3',
				'$1:0',
				'$1$2'
            ],
		$input);
	}
}
