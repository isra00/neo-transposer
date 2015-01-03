<?php

namespace NeoTransposer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Silex\Application;

class NeoApp extends Application
{
	use \Silex\Application\TwigTrait;

	protected $notifications = array('error'=>array(), 'success'=>array());

	public function __construct($config)
	{
		parent::__construct();

		$this['neoconfig'] = $config;

		$this->registerSilexServices();
		$this->registerCustomServices();

		$this['controllers']->before(function(Request $request, Application $app) {
			$app['twig']->addGlobal('neoglobals', array(
				'software_name' => $app['neoconfig']['software_name'],
				'analytics_id'  => $app['neoconfig']['analytics_id'],
				'books'			=> $app['books'],
				'user'			=> $app['session']->get('user'),
				'here'			=> $request->attributes->get('_route'),
				'debug'			=> $this['debug'],
			));

			//Así se salta el enableStrictVariables del Twig-Bridge cuando debug=1
			$this['twig']->disableStrictVariables();
		});

		$this['controllers']->before(function(Request $request, Application $app) {
			if ($redirect = $app['user']->isRedirectionNeeded($request))
			{
				return $app->redirect($app['url_generator']->generate($redirect));
			}
		});
	}

	protected function registerSilexServices()
	{
		$this->register(new \Silex\Provider\TwigServiceProvider(), array(
			'twig.path' => $this['neoconfig']['templates_dir']
		));

		$this->register(new \Silex\Provider\DoctrineServiceProvider(), array(
			'db.options' => array(
				'driver'	=> 'pdo_mysql',
				'host'		=> $this['neoconfig']['db']['host'],
				'user'		=> $this['neoconfig']['db']['user'],
				'password'	=> $this['neoconfig']['db']['password'],
				'dbname'	=> $this['neoconfig']['db']['database'],
				'charset'	=> $this['neoconfig']['db']['charset']
			),
		));

		$this->register(new \Silex\Provider\UrlGeneratorServiceProvider());

		$this['session.storage.options'] = array(
			'cookie_lifetime' => 60,
			//'cookie_lifetime' => 60 * 60 * 24 * 31
		); //1 month.
		$this->register(new \Silex\Provider\SessionServiceProvider());
	}

	protected function registerCustomServices()
	{
		$this['books'] = $this->share(function($app) {
			$books = $app['db']->fetchAll('SELECT * FROM book');
			$books_nice = array();
			foreach ($books as $book)
			{
				$books_nice[$book['id_book']] = $book;
			}
			return $books_nice;
		});

		$this['chord_printers.list'] = array(
			'English' => 'English (F#m, Bb7)',
			'Swahili' => 'Swahili (Fd-, Eb7)',
			'Spanish' => 'Español (Fa#-, Sib7)',
		);

		$this['chord_printers.get'] = $this->protect(function($printer) {
			$printer = "\NeoTransposer\ChordPrinter\ChordPrinter$printer";
			return new $printer();
		});

		if (!$this['session']->get('user'))
		{
			$this['session']->set('user', new User());
		}

		$this['user'] = $this['session']->get('user');
	}

	function addNotification($type, $text)
	{
		$this->notifications[$type][] = $text;
	}

	function render($view, array $parameters = array())
	{
		$this['twig']->addGlobal('notifications', $this->notifications);
		return $this['twig']->render($view, $parameters);
	}
}