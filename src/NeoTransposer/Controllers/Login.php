<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\User;

class Login
{
	public function run(Request $request, \NeoTransposer\NeoApp $app)
	{
		if ('POST' == $request->getMethod())
		{
			return $this->post($request, $app);
		}

		return $this->get($request, $app);
	}

	public function get(Request $request, \NeoTransposer\NeoApp $app, $tpl_vars=array())
	{
		// Log-out always
		$app['session']->set('user', new User);

		$tpl_vars['languages'] = $app['neoconfig']['languages'];
		$tpl_vars['page_title'] = $app->trans('Transpose chords of the songs of the Neocatechumenal Way');
		return $app->render('login.tpl', $tpl_vars);
	}

	public function post(Request $request, \NeoTransposer\NeoApp $app)
	{

		$regexp = <<<REG
[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?
REG;

		if (!preg_match("/$regexp/i", $request->get('email')))
		{
			return $this->get($request, $app, array(
				'error_msg'  => $app->trans('That e-mail doesn\'t look good. Please, re-type it.'),
				'post'		 => array('email' => $request->get('email'))
			));
		}

		if (!$user = User::fetchUserFromEmail($request->get('email'), $app['db']))
		{
			$user = new User($request->get('email'));
			$user->persist($app['db'], $request);
		}

		$app['session']->set('user', $user);

		if (empty($user->lowest_note))
		{
			return $app->redirect($app['url_generator']->generate(
				'user_settings', 
				array('_locale' => $app['locale'], 'firstTime' => '1')
			));
		}
		else
		{
			return $app->redirect($app['url_generator']->generate('book_' . $user->id_book));
		}
	}
}