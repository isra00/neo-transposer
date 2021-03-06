<?php 

namespace NeoTransposer;

/**
 * An abstract class for classes accessing the dependency container from within.
 */
abstract class AppAccess
{
	/**
	 * @var \Silex\Application
	 */
	protected $app;

	public function __construct(\Silex\Application $app)
	{
		$this->app = $app;
	}
}

