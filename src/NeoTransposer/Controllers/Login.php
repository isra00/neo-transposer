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
		$app['session']->set('user', new User);
		$tpl_vars['page_title'] = 'Log-in';
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
				'form_error' => 'That e-mail doesn\'t look good. Please, re-type it.',
				'post'		 => array('email' => $request->get('email'))
			));
		}

		if (!$user = User::fetchUserFromEmail($request->get('email'), $app['db']))
		{
			$user = new User($request->get('email'));
			$user->persist($app['db']);
		}

		$app['session']->set('user', $user);

		if (empty($user->lowest_note))
		{
			return $app->redirect($app['url_generator']->generate('user_settings'));
		}
		else
		{
			return $app->redirect($app['url_generator']->generate('book_' . $user->id_book));
		}
	}
}