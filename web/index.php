<?php

define('START_TIME', microtime(true));

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

$app = new NeoApp(
	require __DIR__ . '/../config.php',
	realpath(__DIR__ . '/..')
);

$needsLogin = function (Request $request, NeoApp $app) {
	if ($redirect = $app['user']->isRedirectionNeeded($request))
	{
		return $app->redirect($app['url_generator']->generate($redirect));
	}
};

$app->get('/', 'NeoTransposer\\Controllers\\Index::get');
$app->get('/sitemap.xml', 'NeoTransposer\\Controllers\\Sitemap::get');

$app->get('/{_locale}/login', 'NeoTransposer\\Controllers\\Login::run')
	->method('GET|POST')
	->bind('login');

$app->get('/{_locale}/user', 'NeoTransposer\\Controllers\\UserSettings::get')
	->bind('user_settings')
	->before($needsLogin);

$app->get('/{_locale}/wizard', 'NeoTransposer\\Controllers\\WizardStepOne::stepOne')
	->method('GET|POST')
	->bind('wizard_step1')
	->before($needsLogin);

$app->post('/{_locale}/wizard/lowest', 'NeoTransposer\\Controllers\\WizardEmpiric::lowest')
	->method('GET|POST')
	->bind('wizard_empiric_lowest')
	->before($needsLogin);

$app->post('/{_locale}/wizard/highest', 'NeoTransposer\\Controllers\\WizardEmpiric::highest')
	->method('GET|POST')
	->bind('wizard_empiric_highest')
	->before($needsLogin);

$app->get('/{_locale}/wizard/finish', 'NeoTransposer\\Controllers\\WizardEmpiric::finish')
	->bind('wizard_finish')
	->before($needsLogin);

$app->get('/set-user-data', 'NeoTransposer\\Controllers\\SetUserData::get')
	->bind('set_user_data')
	->before($needsLogin);

$app->get('/transpose/{id_song}', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->bind('transpose_song');

$app->post('/feedback', 'NeoTransposer\\Controllers\\TranspositionFeedback::post')
	->bind('transposition_feedback');

$app->get('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::get')
	->before($needsLogin);
$app->post('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::post')
	->before($needsLogin);
$app->get('/admin/users', 'NeoTransposer\\Controllers\\AdminUsers::get')
	->before($needsLogin);

$app->get('/static/style.min.css', 'NeoTransposer\\Controllers\\ServeCss::get');

//SEO-friendly URLs for books
foreach ($app['neoconfig']['book_url'] as $id_book=>$slug)
{
	$app->get($slug, 'NeoTransposer\\Controllers\\Book::get')
		->value('id_book', $id_book)
		->bind('book_' . $id_book);
}

//Easter eggs ;-)
$app->get('/get-lucky', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->value('id_song', 118);
$app->get('/sura-yako', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->value('id_song', 319);

$app->run();

if ($app['debug'])
{
	die('<!-- Run in ' . round(microtime(true) - START_TIME, 3) . 's -->');
}