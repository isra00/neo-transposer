<?php 

namespace NeoTransposer;

/**
 * A shortcut for declaring a dependency on the Silex App
 */
abstract class AppAccess
{
	/**
	 * @var NeoApp
	 */
	protected $app;

	public function __construct(\Silex\Application $app)
	{
		$this->app = $app;
	}
}

