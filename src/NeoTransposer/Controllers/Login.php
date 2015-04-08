<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\User;
use \NeoTransposer\Persistence\UserPersistence;

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

		$tpl_vars['languages']				= $app['neoconfig']['languages'];
		$tpl_vars['page_title']				= $app->trans('Transpose the songs of the Neocatechumenal Way · Neo-Transposer');
		$tpl_vars['meta_description']		= $app->trans('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!');
		$tpl_vars['meta_canonical']			= $app['absoluteUriWithoutQuery'];
		$tpl_vars['load_social_buttons']	= true;
		return $app->render('login.tpl', $tpl_vars, true);
	}

	public function post(Request $req, \NeoTransposer\NeoApp $app)
	{

		$regexp = <<<REG
[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?
REG;

		$req_email = trim($req->get('email'));

		if (!preg_match("/$regexp/i", $req_email))
		{
			return $this->get($req, $app, array(
				'error_msg'  => $app->trans('That e-mail doesn\'t look good. Please, re-type it.'),
				'post'		 => array('email' => $req_email)
			));
		}

		if (!$user = UserPersistence::fetchUserFromEmail($req_email, $app['db']))
		{
			$user = new User($req_email);
			$user->persist($app['db'], $req);
		}

		$app['session']->set('user', $user);

		if (empty($user->lowest_note))
		{
			return $app->redirect($app['url_generator']->generate(
				'user_voice', 
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