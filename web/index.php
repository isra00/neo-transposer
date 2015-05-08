<?php

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

$app = new NeoApp(
	require __DIR__ . '/../config.php',
	realpath(__DIR__ . '/..')
);

$needsLogin = function (Request $req, NeoApp $app) {
	if ($redirect = $app['user']->isRedirectionNeeded($req))
	{
		return $app->redirect($app['url_generator']->generate($redirect));
	}
};

$needsAdmin = function (Request $req, NeoApp $app) {
	if ('carallo' != $req->get('t'))
	{
		return new \Symfony\Component\HttpFoundation\Response('Denied', 403);
	}
};

$valid_locales = '(' . implode('|', array_keys($app['neoconfig']['languages'])) . ')';

$controllers = 'NeoTransposer\\Controllers';

$app->get('/', "$controllers\\Index::get");
$app->get('/sitemap.xml', "$controllers\\Sitemap::get");

$app->get('/{_locale}/login', "$controllers\\Login::run")
	->method('GET|POST')
	->assert('_locale', $valid_locales)
	->bind('login');

$app->get('/{_locale}/user/voice', "$controllers\\UserVoice::get")
	->assert('_locale', $valid_locales)
	->bind('user_voice')
	->before($needsLogin);

$app->get('/{_locale}/user/book', "$controllers\\UserBook::get")
	->assert('_locale', $valid_locales)
	->bind('user_book')
	->before($needsLogin);

$app->get('/{_locale}/wizard', "$controllers\\WizardStepOne::stepOne")
	->assert('_locale', $valid_locales)
	->method('GET|POST')
	->bind('wizard_step1')
	->before($needsLogin);

$app->post('/{_locale}/wizard/lowest', "$controllers\\WizardEmpiric::lowest")
	->assert('_locale', $valid_locales)
	->method('GET|POST')
	->bind('wizard_empiric_lowest')
	->before($needsLogin);

$app->post('/{_locale}/wizard/highest', "$controllers\\WizardEmpiric::highest")
	->method('GET|POST')
	->assert('_locale', $valid_locales)
	->bind('wizard_empiric_highest')
	->before($needsLogin);

$app->get('/{_locale}/wizard/finish', "$controllers\\WizardEmpiric::finish")
	->assert('_locale', $valid_locales)
	->bind('wizard_finish')
	->before($needsLogin);

$app->get('/set-user-data', "$controllers\\SetUserData::get")
	->bind('set_user_data')
	->before($needsLogin);

$app->get('/transpose/{id_song}', "$controllers\\TransposeSong::get")
	->bind('transpose_song');

$app->post('/feedback', "$controllers\\TranspositionFeedback::post")
	->bind('transposition_feedback');

$app->get('/insert-song', "$controllers\\InsertSong::get")
	->before($needsLogin)
	->before($needsAdmin);
$app->post('/insert-song', "$controllers\\InsertSong::post")
	->before($needsLogin)
	->before($needsAdmin);
$app->get('/admin/dashboard', "$controllers\\AdminDashboard::get")
	->before($needsLogin)
	->before($needsAdmin);

$app->get('/static/' . $app['neoconfig']['css_cache'] . '.css', "$controllers\\ServeCss::get");

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

$app->run();