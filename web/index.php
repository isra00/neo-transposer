<?php

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;

$app = new \NeoTransposer\NeoApp(
	require __DIR__ . '/../config.php'
);

$needsLogin = function (Request $request, \NeoTransposer\NeoApp $app) {
	if ($redirect = $app['user']->isRedirectionNeeded($request))
	{
		return $app->redirect($app['url_generator']->generate($redirect));
	}
};

$app->get('/transpose/{id_song}', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->bind('transpose_song');

$app->get('/user', 'NeoTransposer\\Controllers\\UserSettings::get')
	->bind('user_settings')
	->before($needsLogin);

$app->get('/set-user-data', 'NeoTransposer\\Controllers\\SetUserData::get')
	->bind('set_user_data')
	->before($needsLogin);

$app->get('/login', 'NeoTransposer\\Controllers\\Login::run')
	->method('GET|POST')
	->bind('login');

$app->get('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::get')
	->before($needsLogin);
$app->post('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::post')
	->before($needsLogin);

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

$app->get('/', 'NeoTransposer\\Controllers\\Index::get');
$app->get('/sitemap.xml', 'NeoTransposer\\Controllers\\Sitemap::get');

$app->run();