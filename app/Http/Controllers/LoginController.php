<?php

namespace App\Http\Controllers;

use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\ValueObject\UserPerformance;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    protected const REGEXP_VALID_EMAIL = "[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?";

    /**
     * Display login page (=landing page).
     *
     * @param array $tplVars Additional vars for Twig, i.e. validation errors.
     */
    public function get(Request $req, BookRepository $bookRepository, string $locale, array $tplVars = [])
    {
        // Log-out always
        $req->session()->flush();
        session(['user' => new User()]);

        $tplVars['all_books']        = $bookRepository->readAllBooks();
        $tplVars['external']         = !empty($req->get('external')); /** @deprecated  */
        $tplVars['page_title']       = __('Transpose the songs of the Neocatechumenal Way · Neo-Transposer');
        $tplVars['meta_description'] = __('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!');
        $tplVars['meta_canonical']   = url()->current();
        $tplVars['page_class']       = 'page-login';

        return response()->view('login', $tplVars);
    }

    /**
     * Receive the login data and reload login if failed to log in, or redirect
     * to voice wizard if user has no voice range, or redirect to book.
     */
    public function post(Request $req, BookRepository $bookRepository, UserRepository $userRepository, string $locale)
    {
        $req_email = trim((string) $req->get('email'));

        $isCaptchaValid = config('app.debug') || config('nt.disable_recaptcha') || $this->validateCaptcha($req, config('nt.recaptcha_secret'));

        if (!preg_match('/' . self::REGEXP_VALID_EMAIL . '/i', $req_email) || !$isCaptchaValid) {
            $errorMsg = $isCaptchaValid ?
                'That e-mail doesn\'t look good. Please, re-type it.'
                : 'The Captcha code is not valid. If you are human, please try again or update your browser to log-in.';

            return $this->get(
                $req,
                $bookRepository,
                $locale,
                [
                    'error_msg'  => __($errorMsg),
                    'post'         => ['email' => $req_email]
                ]
            );
        }

        if (!$user = $userRepository->readFromEmail($req_email)) {
            $idBook = $bookRepository->readIdBookFromLocale($locale);
            $user = new User($req_email, null, null, $idBook, null, null, null, new UserPerformance(0, 0));
            $userRepository->save($user, $req->getClientIp());
        }

        // @todo firstTime podría ser un método en vez de un atributo si no se fuerza en otras partes?
        $user->firstTime = !$user->hasRange();
        session(['user' => $user]);

        if ($user->firstTime) {
            return redirect()->route('user_voice', ['locale' => $locale, 'firstTime' => '1']);
        }

        $idBook = $user->id_book ?? 1;
        return redirect($req->get('redirect') ?? route("book_$idBook"));
    }

    private function validateCaptcha(Request $req, string $secret): bool
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            http_build_query([
                'secret' => $secret,
                'response' => $req->get('g-recaptcha-response')
            ])
        );

        $response = curl_exec($curl);
        curl_close($curl);

        return (true == json_decode($response, true)['success']);
    }

}
