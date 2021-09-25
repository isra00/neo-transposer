<?php

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;
use \Silex\Provider;

$app = new NeoApp(
	require __DIR__ . '/../config.php',
	realpath(__DIR__ . '/..')
);

$needsLogin = function (Request $req, NeoApp $app) {
	
	//Locale necessary for Admin pages, which set no es/sw locale.
	if ($redirect = $app['neouser']->isRedirectionNeeded($req))
	{
		return $app->redirect($app['url_generator']->generate($redirect, array(
			'_locale' => ('en' == $app['locale']) ? 'es' : $app['locale']
		)));
	}
};

$app->register(new Provider\SecurityServiceProvider(), array(
	'security.firewalls' => array(
		'test' => array(
			'pattern'	=> '^/admin',
			'http'		=> true,
			'users'		=> $app['neoconfig']['admins'],
		)
	)
));

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

$controllers = 'NeoTransposer\\Controllers';

$app->get('/', "$controllers\\Index::get");
$app->get('/sitemap.xml', "$controllers\\Sitemap::get");

$app->get('/{_locale}/login', "$controllers\\Login::run")
	->method('GET|POST')
	->assert('_locale', $validLocales)
	->bind('login');

$app->get('/{_locale}/user/voice', "$controllers\\UserVoice::get")
	->assert('_locale', $validLocales)
	->bind('user_voice')
	->before($needsLogin);

$app->get('/{_locale}/user/book', "$controllers\\UserBook::get")
	->assert('_locale', $validLocales)
	->bind('user_book')
	->before($needsLogin);

$app->get('/{_locale}/wizard', "$controllers\\WizardStepOne::stepOne")
	->assert('_locale', $validLocales)
	->method('GET|POST')
	->bind('wizard_step1')
	->before($needsLogin);

$app->post('/{_locale}/wizard/lowest', "$controllers\\WizardEmpiric::lowest")
	->assert('_locale', $validLocales)
	->method('GET|POST')
	->bind('wizard_empiric_lowest')
	->before($needsLogin);

$app->post('/{_locale}/wizard/highest', "$controllers\\WizardEmpiric::highest")
	->method('GET|POST')
	->assert('_locale', $validLocales)
	->bind('wizard_empiric_highest')
	->before($needsLogin);

$app->get('/set-user-data', "$controllers\\SetUserData::get")
	->bind('set_user_data')
	->before($needsLogin);

$app->get('/transpose/{id_song}', "$controllers\\TransposeSong::get")
	->bind('transpose_song');

$app->post('/feedback', "$controllers\\ReceiveFeedback::post")
	->bind('transposition_feedback');

$app->get('/{_locale}/all-songs-report', "$controllers\\AllSongsReport::get")
	->assert('_locale', $validLocales)
	->bind('all_songs_report')
	->before($needsLogin);

$app->get('/{_locale}/manifest.json', "$controllers\\WebManifest::get")
	->assert('_locale', $validLocales)
	->bind('webmanifest');

$app->get('/{_locale}/external-login-finish', "$controllers\\Login::externalLoginFinish")
	->assert('_locale', $validLocales)
	->bind('external_login_finish')
	->before($needsLogin);
	
$app->get('/admin/insert-song', "$controllers\\InsertSong::get");
$app->post('/admin/insert-song', "$controllers\\InsertSong::post");
$app->get('/admin/dashboard', "$controllers\\AdminDashboard::get");
$app->get('/admin/chord-correction', "$controllers\\ChordCorrectionPanel::get");
$app->post('/admin/chord-correction', "$controllers\\ChordCorrectionPanel::post")
	->bind('chord_correction_panel');

$app->get('/static/compiled-' . $app['neoconfig']['css_cache'] . '.css', "$controllers\\ServeCss::get");

//SEO-friendly URLs for books
foreach ($app['neoconfig']['book_url'] as $id_book=>$slug)
{
	$app->get($slug, "$controllers\\Book::get")
		->value('id_book', $id_book)
		->bind('book_' . $id_book);
}

//Easter eggs ;-)
$app->get('/get-lucky', "$controllers\\TransposeSong::get")
	->value('id_song', 118);
$app->get('/sura-yako', "$controllers\\TransposeSong::get")
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
