<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\User;

class Login
{
	public function run(Request $req, \NeoTransposer\NeoApp $app)
	{
		if ('POST' == $req->getMethod())
		{
			return $this->post($req, $app);
		}

		return $this->get($req, $app);
	}

	public function get(Request $req, \NeoTransposer\NeoApp $app, $tpl_vars=array())
	{
		// Log-out always
		$app['session']->set('user', new User);

		$tpl_vars['languages'] = $app['neoconfig']['languages'];
		$tpl_vars['page_title'] = $app->trans('Transpose chords of the songs of the Neocatechumenal Way');
		return $app->render('login.tpl', $tpl_vars);
	}

	public function post(Request $req, \NeoTransposer\NeoApp $app)
	{

		$regexp = <<<REG
[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?
REG;

		if (!preg_match("/$regexp/i", $req->get('email')))
		{
			return $this->get($req, $app, array(
				'error_msg'  => $app->trans('That e-mail doesn\'t look good. Please, re-type it.'),
				'post'		 => array('email' => $req->get('email'))
			));
		}

		if (!$user = User::fetchUserFromEmail($req->get('email'), $app['db']))
		{
			$user = new User($req->get('email'));
			$user->persist($app['db'], $req);
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
			$target = $req->get('redirect')
				? $req->get('redirect')
				: $app['url_generator']->generate('book_' . $user->id_book);

			return $app->redirect($target);
		}
	}
}