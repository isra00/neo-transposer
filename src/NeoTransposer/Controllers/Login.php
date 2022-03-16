<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\User;
use \NeoTransposer\Persistence\UserPersistence;
use \NeoTransposer\NeoApp;

/**
 * Landing page with Login form.
 */
class Login
{
	protected const REGEXP_VALID_EMAIL = "[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?";

    /**
     * Display login page (=landing page).
     *
     * @param \Symfony\Component\HttpFoundation\Request $req
     * @param \NeoTransposer\NeoApp $app
     * @param $tpl_vars Additional vars for Twig, i.e. validation errors.
     * @return string
     */
	public function get(Request $req, NeoApp $app, $tpl_vars=[]): string
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

    /**
     * Receive the login data and reload login if failed to log in, or redirect to voice wizard if user has
     * no voice range, or redirect to book.
     *
     * @param \Symfony\Component\HttpFoundation\Request $req
     * @param \NeoTransposer\NeoApp $app
     * @return string|\Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function post(Request $req, NeoApp $app)
	{
		$req_email = trim($req->get('email'));

		$isCaptchaValid = $app['debug'] || $app['neoconfig']['disable_recaptcha'] || $this->validateCaptcha($req);

		if (!preg_match('/' . self::REGEXP_VALID_EMAIL . '/i', $req_email) || !$isCaptchaValid)
		{
			$errorMsg = $isCaptchaValid ?
						'That e-mail doesn\'t look good. Please, re-type it.'
						: 'The Captcha code is not valid. If you are human, please try again or update your browser to log-in.';

			return $this->get($req, $app, [
				'error_msg'  => $app->trans($errorMsg),
				'post'		 => ['email' => $req_email]
            ]);
		}

		$userPersistence = new UserPersistence($app['db']);

		if (!$user = $userPersistence->fetchUserFromEmail($req_email))
		{
			$user = new User($req_email, null, null);
			$user->persist($app['db'], $req->getClientIp());
		}

		$user->firstTime = !$user->hasRange();
		$app['session']->set('user', $user);

		if ($user->firstTime)
		{
			return $app->redirect($app->path(
				'user_voice',
				array('_locale' => $app['locale'], 'firstTime' => '1')
			));
		}

        $idBook = $user->id_book ?? 1;

        $target = $req->get('redirect')
            ?? $app->path("book_$idBook");

        if (!empty($app['session']->get('callbackSetUserToken')))
        {
            $target = $app->path('external_login_finish');
        }

        return $app->redirect($target);
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
