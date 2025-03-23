<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;
use Illuminate\Http\RedirectResponse;

class IndexController extends Controller
{
    /**
     * If logged in, redirect to the book. If not, redirect to login in the language of the browser
     * (Accept-Language header).
     *
     * @param Request $req The HTTP request.
     * @param NeoApp  $app The NeoApp.
     *
     * @return RedirectResponse A redirection to the proper page.
     */
	public function get(Request $req, IpToLocaleResolver $ipToLocaleResolver): RedirectResponse
    {
		$this->setLocaleAutodetect($req, $ipToLocaleResolver);

		if (session('user')->id_book)
		{
			return redirect()->route(
				'book_' . session('user')->id_book
			);
		}

		return redirect()->route(
			'login',
			['locale' => App::getLocale()]
		);
	}
}
