<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the / route, which has NO CONTENTS.
 */
class Index
{
	/**
	 * If logged in, redirect to the book. If not,
	 * redirect to login in the language of the browser (Accept-Language header).
	 * 
	 * @param  Request               $req The HTTP request.
	 * @param  \NeoTransposer\NeoApp $app The NeoApp.
	 * @return RedirectResponse      A redirection to the proper page.
	 */
	public function get(Request $req, \NeoTransposer\NeoApp $app)
	{
		$app->setLocaleAutodetect($req);

		if ($app['neouser']->id_book)
		{
			return $app->redirect($app['url_generator']->generate(
				'book_' . $app['neouser']->id_book
			));
		}

		/* 
		 * New Domain 2016 redirections: the general policy is to redirect
		 * everything to the new domain with 301. BUT since we have a lot of
		 * direct access users, we want to show them a pop-up (in the new domain)
		 * alerting them about the change. So if we detect no referer, we
		 * redirect adding the param ?newdomain that will trigger the pop-up.
		 * This is done in .htaccess. But there is still a little problem: home
		 * page / redirects to language-based login page making us lose the 
		 * referer. We solve this here, by redirecting directly to the new
		 * domain. If we are in the new domain, do the normal stuff.
		 */

		$redirectUrl = '';
		$params = array('_locale' => $app['locale']);

		//If we are in the old domain, redirect to the new one
		if ($req->getHost() != $app['neoconfig']['new_domain'])
		{
			$redirectUrl = $req->getScheme() . '://' . $app['neoconfig']['new_domain'];

			if (empty($req->headers->get('referer')))
			{
				$params['newdomain'] = true;
			}

			//We track the referer in any case for analytics
			$params['referer'] = $req->headers->get('referer');
		}

		$redirectUrl .= $app['url_generator']->generate(
			'login',
			$params
		);

		return $app->redirect($redirectUrl);
	}
}
