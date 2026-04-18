<?php

namespace App\Http\Controllers;

final class ServeCssController extends Controller
{
    private const SRC_FILE = 'static/style.css';
    public const MIN_FILE_PATTERN = 'static/compiled-%s.css';

    /**
     * Invoked when Apache doesn't find the compiled CSS file. Re-minifies style.css,
     * writes the new compiled-<hash>.css, patches the config, and redirects the
     * browser to the new file, which Apache will then serve statically from then on.
     */
    public function get()
    {
        $minified = $this->minifyCss(file_get_contents(public_path(self::SRC_FILE)));
        $hash = md5((string) $minified);

        $relativeNewPath = sprintf(self::MIN_FILE_PATTERN, $hash);
        file_put_contents(public_path($relativeNewPath), $minified);

        $configFile = config_path('nt.php');
        $configSrc = file_get_contents($configFile);
        $configSrc = preg_replace(
            "/(\s*'css_cache'\s*=>\s*')([a-f\d]{32})(',\s*)/",
            "\${1}$hash\${3}",
            $configSrc
        );
        file_put_contents($configFile, $configSrc);

        return redirect('/' . $relativeNewPath);
    }

    /**
     * @url https://gist.github.com/Rodrigo54/93169db48194d470188f
     */
    public function minifyCss(string $input): string
    {
        if (trim($input) === '') {
            return $input;
        }
        return preg_replace(
            [
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                '#(background-position):0(?=[;\}])#si',
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
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
                '$1$2',
            ],
            $input
        );
    }
}
