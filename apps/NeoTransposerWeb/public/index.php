<?php

require __DIR__ . '/../../../vendor/autoload.php';

use NeoTransposerApp\Infrastructure\LoginFlow;
use Symfony\Component\HttpFoundation\Request;
use NeoTransposerWeb\NeoApp;
use Silex\Provider;

$app = new NeoApp(
	require __DIR__ . '/../config.php',
	realpath(__DIR__ . '/..')
);

$needsLogin = function (Request $req, NeoApp $app) {

	if ($redirect = LoginFlow::redirectIfUserDoesNotComply($req->attributes->get('_route'), $app['neouser']))
	{
    	//Locale necessary for Admin pages, which set no es/sw locale.
		return $app->redirect($app['url_generator']->generate($redirect, [
			'_locale' => ('en' == $app['locale']) ? 'es' : $app['locale']
        ]));
	}
};

/** @todo Mover a NeoApp */
$app->register(new Provider\SecurityServiceProvider(), array(
	'security.firewalls' => array(
		'test' => array(
			'pattern'	=> '^/admin',
			'http'		=> true,
			'users'		=> $app['neoconfig']['admins'],
		)
	)
));

/** @todo Mover a NeoApp */
if (!empty($app['neoconfig']['profiler']))
{
	if ($app['debug'])
	{
		$app->register(new Provider\HttpFragmentServiceProvider());
		$app->register(new Provider\ServiceControllerServiceProvider());
		$app->register(new Provider\WebProfilerServiceProvider(), array(
		    'profiler.cache_dir' 	=> __DIR__ . '/../cache/profiler',
		    'profiler.mount_prefix' => '/admin/_profiler',
		));
	}
}

$validLocales = '(' . implode('|', array_keys($app['neoconfig']['languages'])) . ')';

$app->get('/', \NeoTransposerWeb\Controllers\Index::class . '::get');
$app->get('/sitemap.xml', \NeoTransposerWeb\Controllers\Sitemap::class . '::get');

$app->get('/{_locale}/login', \NeoTransposerWeb\Controllers\Login::class . '::get')
	->method('GET')
	->assert('_locale', $validLocales)
	->bind('login');

$app->get('/{_locale}/login', \NeoTransposerWeb\Controllers\Login::class . '::post')
	->method('POST')
	->assert('_locale', $validLocales);

$app->get('/{_locale}/user/voice', \NeoTransposerWeb\Controllers\UserVoice::class . '::get')
	->assert('_locale', $validLocales)
	->bind('user_voice')
	->before($needsLogin);

$app->get('/{_locale}/user/book', \NeoTransposerWeb\Controllers\UserBook::class . '::get')
	->assert('_locale', $validLocales)
	->bind('user_book')
	->before($needsLogin);

$app->get('/{_locale}/wizard', \NeoTransposerWeb\Controllers\WizardSelectStandard::class . '::showPage')
	->assert('_locale', $validLocales)
	->method('GET')
	->bind('wizard_step1')
	->before($needsLogin);

$app->get('/{_locale}/wizard/select-standard', \NeoTransposerWeb\Controllers\WizardSelectStandard::class . '::selectStandardAndShowInstructionsPage')
	->assert('_locale', $validLocales)
	->method('GET')
	->bind('wizard_select_standard')
	->before($needsLogin);

$app->post('/{_locale}/wizard/lowest', \NeoTransposerWeb\Controllers\WizardEmpiric::class . '::lowest')
	->assert('_locale', $validLocales)
	->method('GET|POST')
	->bind('wizard_empiric_lowest')
	->before($needsLogin);

$app->post('/{_locale}/wizard/highest', \NeoTransposerWeb\Controllers\WizardEmpiric::class . '::highest')
	->method('GET|POST')
	->assert('_locale', $validLocales)
	->bind('wizard_empiric_highest')
	->before($needsLogin);

$app->get('/set-user-data', \NeoTransposerWeb\Controllers\SetUserData::class . '::get')
	->bind('set_user_data')
	->before($needsLogin);

$app->get('/transpose/{id_song}', \NeoTransposerWeb\Controllers\TransposeSong::class . '::get')
	->bind('transpose_song');

$app->post('/feedback', \NeoTransposerWeb\Controllers\ReceiveFeedback::class . '::post')
	->bind('transposition_feedback');

$app->get('/{_locale}/all-songs-report', \NeoTransposerWeb\Controllers\AllSongsReport::class . '::get')
	->assert('_locale', $validLocales)
	->bind('all_songs_report')
	->before($needsLogin);

$app->get('/{_locale}/manifest.json', \NeoTransposerWeb\Controllers\WebManifest::class . '::get')
	->assert('_locale', $validLocales)
	->bind('webmanifest');

$app->get('/{_locale}/external-login-finish', \NeoTransposerWeb\Controllers\Login::class . '::externalLoginFinish')
	->assert('_locale', $validLocales)
	->bind('external_login_finish')
	->before($needsLogin);

$app->get('/admin/insert-song', \NeoTransposerWeb\Controllers\InsertSong::class . '::get');
$app->post('/admin/insert-song', \NeoTransposerWeb\Controllers\InsertSong::class . '::post');
$app->get('/admin/dashboard', \NeoTransposerWeb\Controllers\AdminDashboard::class . '::get');
$app->get('/admin/chord-correction', \NeoTransposerWeb\Controllers\ChordCorrectionPanel::class . '::get');
$app->post('/admin/chord-correction', \NeoTransposerWeb\Controllers\ChordCorrectionPanel::class . '::post')
	->bind('chord_correction_panel');

$app->get('/static/compiled-' . $app['neoconfig']['css_cache'] . '.css', \NeoTransposerWeb\Controllers\ServeCss::class . '::get');

//SEO-friendly URLs for books
foreach ($app['neoconfig']['book_url'] as $id_book=>$slug)
{
    /** @todo Si aquí se definen las URL no tiene sentido que estas estén en config.php! En este caso un poco de código duplicado está mucho mejor. */
	$app->get($slug, \NeoTransposerWeb\Controllers\Book::class . '::get')
		->value('id_book', $id_book)
		->bind('book_' . $id_book);
}

//Easter eggs ;-)
$app->get('/get-lucky', \NeoTransposerWeb\Controllers\TransposeSong::class . '::get')
	->value('id_song', 118);
$app->get('/sura-yako', \NeoTransposerWeb\Controllers\TransposeSong::class . '::get')
	->value('id_song', 319);

$app->get('/{_locale}/manifesto', function() use ($app)
{
	return $app->render('pages/manifesto.' . $app['locale'] . '.twig', array(
		'page_title' => $app->trans('Manifesto'),
	));
})->assert('_locale', 'es')
	->bind('manifesto');

$app->get('/{_locale}/people-compatible-transpositions', function() use ($app)
{
	$templateFile = 'pages/people-compatible-info.' . $app['locale'] . '.twig';

	if (!file_exists($app['neoconfig']['templates_dir'] . '/' . $templateFile))
	{
		$templateFile = 'pages/people-compatible-info.en.twig';
	}

	return $app->render($templateFile, array(
		'page_title' 	=> $app->trans('People-compatible transpositions'),
	));
})->assert('_locale', $validLocales)
	->bind('people-compatible-info');

$app->run();