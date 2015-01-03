<?php

require '../vendor/autoload.php';

$app = new \NeoTransposer\NeoApp(
	require __DIR__ . '/../config.php',
	__DIR__ . '../templates'
);

$app->get('/book/{id_book}', 'NeoTransposer\\Controllers\\Book::get')
	->assert('id_book', '\d+')
	->bind('book');

$app->get('/transpose/{id_song}', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->assert('id_song', '\d+')
	->bind('transpose_song');

$app->get('/user', 'NeoTransposer\\Controllers\\UserSettings::get')
	->bind('user_settings');

$app->get('/set-user-data', 'NeoTransposer\\Controllers\\SetUserData::get')
	->bind('set_user_data');

$app->get('/login', 'NeoTransposer\\Controllers\\Login::run')
	->method('GET|POST')
	->bind('login');

$app->get('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::get');
$app->post('/insert-song', 'NeoTransposer\\Controllers\\InsertSong::post');

//Easter egg ;-)
$app->get('/get-lucky', 'NeoTransposer\\Controllers\\TransposeSong::get')
	->value('id_song', 118);

$app->get('/', 'NeoTransposer\\Controllers\\Index::get');

$app['debug'] = true;

$app->run();