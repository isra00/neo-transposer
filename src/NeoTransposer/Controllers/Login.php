<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\User;
use \NeoTransposer\Persistence\UserPersistence;
use \MaxMind\WebService\Http\CurlRequest;

/**
 * Landing page with Login form.
 */
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
		$app['session']->clear();
		$app['session']->set('user', new User);

		if (!empty($req->get('callbackSetUserToken')))
		{
			$app['session']->set('callbackSetUserToken', $req->get('callbackSetUserToken'));
		}

		$tpl_vars['external'] 				= !empty($req->get('external'));
		$tpl_vars['languages']				= $app['neoconfig']['languages'];
		$tpl_vars['page_title']				= $app->trans('Transpose the songs of the Neocatechumenal Way · Neo-Transposer');
		$tpl_vars['meta_description']		= $app->trans('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!');
		$tpl_vars['meta_canonical']			= $app['absoluteUriWithoutQuery'];
		return $app->render('login.twig', $tpl_vars, true);
	}

	public function post(Request $req, \NeoTransposer\NeoApp $app)
	{

		$regexp = <<<REG
[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?
REG;

		$req_email = trim($req->get('email'));

		if (!preg_match("/$regexp/i", $req_email) || !$this->validateCaptcha($req))
		{
			return $this->get($req, $app, array(
				'error_msg'  => $app->trans('That e-mail doesn\'t look good. Please, re-type it.'),
				'post'		 => array('email' => $req_email)
			));
		}

		$userPersistence = new UserPersistence($app['db']);

		if (!$user = $userPersistence->fetchUserFromEmail($req_email))
		{
			$user = new User($req_email, null, null);
			$user->persist($app['db'], $req->getClientIp());
		}

		$app['session']->set('user', $user);

		if (empty($user->range->lowest))
		{
			return $app->redirect($app->path(
				'user_voice', 
				array('_locale' => $app['locale'], 'firstTime' => '1')
			));
		}
		else
		{
			$id_book = !empty($user->id_book) ? $user->id_book : 1;

			$target = $req->get('redirect')
				? $req->get('redirect')
				: $app->path("book_$id_book");

			if (!empty($app['session']->get('callbackSetUserToken')))
			{
				$target = $app->path('external_login_finish');
			}

			return $app->redirect($target);
		}
	}

	protected function validateCaptcha(Request $req)
	{

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
			'secret' => '6LfXByMUAAAAAByHDr2AzwKA0P_26Oqz-RxZvrez',
			'response' => $req->get('g-recaptcha-response')
		]));

		$response = curl_exec($curl);
		curl_close($curl);

		return (true == json_decode($response, true)['success']);
	}

	public function externalLoginFinish(\NeoTransposer\NeoApp $app)
	{
		return $app->render('external_login_finish.twig');
	}
}
