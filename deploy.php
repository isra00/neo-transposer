<?php

$deployDir		= '/var/www/html/vhosts/transposer.local'; // No trailing slash
$composerHome 	= '/tmp/composer-www-data';
$composerPhar	= '/var/www/html/composer.phar';
$repo			= 'isra00/neo-transposer';
$deployBranch	= 'master';

$githubBase		= 'https://github.com/' . $repo;
$githubApiBase	= 'https://api.github.com';

define('TIME_START', microtime(true));
ini_set("display_errors", true);
error_reporting(E_ALL);

$neoConfig	= include "$deployDir/config.php";

function runCommand($command)
{
    $start = microtime(true);
    exec($command, $output, $status);

    return array(
        'command' => $command,
        'output'  => $output,
        'status'  => $status,
        'time'    => microtime(true) - $start
    );
}

function runCommands($commands, $stopIfStatusNotZero = true)
{
    $executeNext = true;
    $actions = array();
    foreach ($commands as $command)
    {
        $output = array();
        $status = 'not-executed';
        $start   = microtime(true);

        if ($executeNext)
        {
            exec($command, $output, $status);

            if ($status != 0)
            {
                $executeNext = !$stopIfStatusNotZero;
            }
        }

        $actions[] = array(
            'command' => $command,
            'output'  => $output,
            'status'  => $status,
            'time'    => microtime(true) - $start
        );
    }
    return $actions;
}

function getLastCommitInLocal($deployDir)
{
    $gitLog = runCommand("cd $deployDir && git log --pretty=medium");

    preg_match('/^commit ([0-9a-f]{40})$/', $gitLog['output'][0], $matchHash);
    $hash = $matchHash[1];

    //When last commit is a merge, new lines are printed, so date is line 4
    $dateLine = ("Merge" == substr($gitLog['output'][1], 0, 5))
        ? 3
        : 2;

    preg_match('/^Date\:\s+(.*)$/i', $gitLog['output'][$dateLine], $matchDate);
    $date = $matchDate[1];

    return [
        'hash' 		=> substr($hash, 0, 6),
        'date' 		=> $date,
        'message' 	=> trim($gitLog['output'][4])
    ];

    return $gitLog;
}

function githubApiRequest($url)
{
    global $githubApiBase;

    $options = [
        'http' => [
            'header' => ['User-Agent: PHP']
        ]
    ];

    return json_decode(
        file_get_contents($githubApiBase . $url, false, stream_context_create($options)),
        true
    );
}

function getLaterCommits(DateTime $since)
{
    global $repo, $githubApiBase;

    $since->setTimezone(new DateTimeZone('UTC'));
    $apiUrl = '/repos/' . $repo . '/commits?sha=master&since=' . $since->format('Y-m-d\TH:i:s\Z');
    $commits = githubApiRequest($apiUrl);

    $commitsBySha = [];

    foreach ($commits as $commit)
    {
        $commitsBySha[$commit['sha']] = $commit;
    }

    //$builds = json_decode(file_get_contents('https://api.travis-ci.org/repos/' . $repo . '/builds'), true);
    $ghactionsJson = '';
    exec('curl   -H "Accept: application/vnd.github.v3+json"   https://api.github.com/repos/isra00/neo-transposer/actions/workflows/test.yml/runs', $ghactionsJson);
    $ghactionsJson = implode("", $ghactionsJson);
    $builds = json_decode($ghactionsJson, true);
    foreach ($builds['workflow_runs'] as $build)
    {
        if (isset($commitsBySha[$build['head_sha']]))
        {
            $commitsBySha[$build['head_sha']]['build'] = $build;
        }
    }

    return $commitsBySha;
}

if (isset($_POST['sent']))
{
    $nothingToDeploy = false;

    $actions = array();

    $actions[] = runCommand("cd $deployDir && git pull 2>&1");

    if ('Already up-to-date.' != trim($actions[0]['output'][0]))
    {
        $actions[0]['status'] = 0;
        if ($actions[0]['status'] != 1)
        {
            if (!empty($_POST['clear-twig-cache']))
            {
                $commandRmTwig = runCommand("rm -rfv $deployDir/cache/twig/* | wc -l");
                $commandRmTwig['output'][0] = ($commandRmTwig['status'] != 1)
                    ? $commandRmTwig['output'][0] . ' files deleted'
                    : $commandRmTwig['output'][0];

                $actions[] = $commandRmTwig;
            }

            if (!empty($_POST['rebuild-css']))
            {
                $start = microtime(true);
                require $deployDir . '/vendor/autoload.php';

                $app = new NeoTransposer\NeoApp(
                    require __DIR__ . '/config.php',
                    realpath(__DIR__)
                );

                $serveCssController = new \NeoTransposer\Controllers\ServeCss;

                $actions[] = array(
                    'command' => 'Re-compile CSS',
                    'output'  => ['Generated new file ' . $serveCssController->get($app)->getTargetUrl()],
                    'status'  => '(n/a)',
                    'time'    => microtime(true) - $start
                );
            }

            if (!empty($_POST['composer-install']))
            {
                chdir($deployDir);
                $actions[] = runCommand("export COMPOSER_HOME=$composerHome && $composerPhar install 2>&1");
            }

            if (!empty($_POST['reload-db']))
            {
                $commandMysql = runCommand("mysql -h{$neoConfig['db']['host']} -u{$neoConfig['db']['user']} -p{$neoConfig['db']['password']} {$neoConfig['db']['database']} < $deployDir/song_data.sql 2>&1");
                $commandMysql['command'] = str_replace($neoConfig['db'], '*****', $commandMysql['command']);
                $actions[] = $commandMysql;
            }

            $success = true;
        }
        else
        {
            $success = false;
        }
    }
    else
    {
        $nothingToDeploy = true;
    }

    foreach ($actions as $action)
    {
        if ($action['status'] != 0)
        {
            $success = false;
        }
    }
}

$lastCommitInLocal = getLastCommitInLocal($deployDir);
$laterCommits = getLaterCommits(new DateTime($lastCommitInLocal['date']));

$lastCommit = current($laterCommits);
$willDeployBroken = isset($lastCommit['build'])
    ? ($lastCommit['build']['conclusion'] == 'failure')
    : false;

$whoami = runCommand("whoami")['output'][0];

$cssDate = file_exists("$deployDir/web/static/compiled-" . $neoConfig['css_cache'] . ".css")
    ? date('d/m/Y H:i:s', filectime("$deployDir/web/static/compiled-" . $neoConfig['css_cache'] . ".css"))
    : 'no file';

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Deploy from git</title>
    <style>
        body { font-family: helvetica, arial, sans-serif; color: #222; margin: 2%; }
        time { background: gray; color: white; border-top-left-radius: .5em; border-top-right-radius: .5em; font-size: .8em; font-weight: bold; padding: .3em .6em; display: inline-block;}
        pre { background: #eee; margin-top: 0; padding: .5em; line-height: 140%; }
        pre.failed { border: 1px solid red; }
        .error { color: red; }
        h1.success { color: rgb(56, 118, 29); }
        .nothing { color: gray; }
        .prompt { color: green; }
        .failed .prompt { color: red; }
        .big { font-size: 1.5em; }

        .last-commits { border: 1px solid #eee; border-radius: 6px; width: auto; margin: 1em 0; font-size: .9em; }
        .last-commits h3 { color: #555; margin: 0; padding: .3em .5em; border-bottom: 1px solid #eee; font-size: 1em; background: #ddd; border-radius: 5px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
        .last-commits p { padding: .5em; margin: 0; }
        .last-commits a { color: #444; text-decoration: none; }

        .last-commits date { display: block; text-transform: uppercase; font-size: .85em; color: #555; }
        .last-commits date .status { font-size: 1.5em; }
        .last-commits date .status.created { color: #dbab09; }
        .last-commits date .status.created::after { content: "●"; }
        .last-commits date .status.success { color: green; }
        .last-commits date .status.success::after { content: "✔"; }
        .last-commits date .status.failure { color: red; }
        .last-commits date .status.failure::after { content: "✖"; }

        .last-commits code { padding-left: 1em; margin-right: 1em; color: #009393; }
        .last-commits a span { font-weight: bold; }
        .last-commits b { display: inline-block; background: #009393; color: white; padding: .2em .3em; border-radius: .2em; font-size: .9em; }


        .submit { display: block; margin: .5em 0; }

        form span { display: inline-block; margin: .2em; }
        small { color: #555; }
        label { display: inline-block; vertical-align: top; }
    </style>
</head>
<body>

<?php if (isset($_POST['sent'])) : ?>

    <?php if (!empty($success)) : ?>
        <h1 class="success">Deployed in <?php echo round(microtime(true) - TIME_START, 3) ?>s</h1>
    <?php elseif ($nothingToDeploy) : ?>
        <h1 class="nothing">Nothing to deploy</h1>
    <?php else : ?>
        <h2 class='error'>Deploy failed</h2>
    <?php endif ?>

    <?php foreach ($actions as $action) : ?>
        <time><?php echo round($action['time'], 3) . 's | Status code: ' . $action['status'] ?></time>
        <pre class="<?php if ($action['status'] != 0) echo 'failed' ?>">
<strong><span class="prompt"><?php echo $whoami ?>$</span> <?php echo $action['command'] ?></strong>
<?php echo implode("\n", $action['output']) ?>
		</pre>
    <?php endforeach ?>

    <?php if (!empty($success)) : ?>
        <h4>Go and test now: <a href="http://neo-transposer.com">neo-transposer.com</a> · <a href="http://neo-transposer.com/admin/dashboard?tool=testAllTranspositions">testAllTranspositions</a></h4>
    <?php endif ?>

<?php endif ?>

<section class="last-commits">
    <h3>Last commits in <?php echo $deployBranch ?></h3>
    <?php foreach ($laterCommits as $index=>$commit) : ?>
        <p>
            <date>
                <?php echo date('r', strtotime($commit['commit']['author']['date'])) ?>
                <?php if (!empty($commit['build'])) : ?>
                    <a href="https://github.com/isra00/neo-transposer/actions/runs/1890234087" class="status <?php echo $commit['build']['conclusion'] ?>" title="Build <?php echo $commit['build']['conclusion'] ?> at GitHub actions"></a>
                <?php endif ?>
            </date>
            <a target="_blank" href="<?php echo $githubBase ?>/commit/<?php echo $commit['sha'] ?>" target="_blank">
                <span><?php echo $commit['commit']['message'] ?></span>
            </a>
            <code><?php echo substr($commit['sha'], 0, 6) ?></code>
            <?php if (array_keys($laterCommits)[0] == $index) : ?><b>&larr; HEAD</b><?php endif ?>
            <?php if (array_keys($laterCommits)[count($laterCommits) - 1] == $index) : ?><b>&larr; Deployed</b><?php endif ?>
        </p>
    <?php endforeach ?>

</section>

<form method="post">

		<span>
			<input type="checkbox" id="clear-twig-cache" name="clear-twig-cache" checked>
			<label for="clear-twig-cache">Clear Twig cache</label>
		</span>

    <span>
			<input type="checkbox" id="rebuild-css" name="rebuild-css" checked>
			<label for="rebuild-css">Recompile CSS <br><small>[<?php echo $cssDate ?>]</small></label>
		</span>

    <span>
			<input type="checkbox" id="reload-db" name="reload-db">
			<label for="reload-db">Reload DB</label>
		</span>

    <span>
			<input type="checkbox" id="composer-install" name="composer-install">
			<label for="composer-install">Composer install <br><small>(vendor &larr; composer.lock)</small></label>
		</span>

    <span class="submit">
			<button type="submit" name="sent" class="big">
			<?php echo ($willDeployBroken) ? 'Deploy BROKEN build now' : 'Deploy now' ?>
			</button>
		</span>
</form>
</body>
</html>