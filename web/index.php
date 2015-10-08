<?php

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

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

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
	'security.firewalls' => array(
		'test' => array(
			'pattern'	=> '^/admin',
			'http'		=> true,
			'users'		=> $app['neoconfig']['admins'],
		)
	)
));

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

$app->get('/{_locale}/all-songs-report', "$controllers\\AllSongsReport::get")
	->assert('_locale', $valid_locales)
	->bind('all_songs_report')
	->before($needsLogin);
	
$app->get('/{_locale}/all-songs-report/pdf', "$controllers\\AllSongsReport::getPdf")
	->assert('_locale', $valid_locales)
	->bind('all_songs_report_pdf')
	->before($needsLogin);

$app->get('/admin/insert-song', "$controllers\\InsertSong::get")
	->before($needsLogin);
$app->post('/admin/insert-song', "$controllers\\InsertSong::post")
	->before($needsLogin);
$app->get('/admin/dashboard', "$controllers\\AdminDashboard::get")
	->before($needsLogin);
$app->get('/admin/chord-correction', "$controllers\\ChordCorrectionPanel::get")
	->before($needsLogin);
$app->post('/admin/chord-correction', "$controllers\\ChordCorrectionPanel::post")
	->bind('chord_correction_panel')
	->before($needsLogin);

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