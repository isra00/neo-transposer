<?php

namespace NeoTransposer;

use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * An extension of Silex Application with custom stuff.
 */
final class NeoApp extends Application
{
    use \Silex\Application\TwigTrait;
    use \Silex\Application\TranslationTrait;
    use \Silex\Application\UrlGeneratorTrait;

    private array $notifications = ['error' => [], 'success' => []];

    private $hostname;

    /** Defined by SEO rules */
    protected const PAGE_TITLE_MAX_LENGTH = 55;

    /**
     * Load config, register services in Silex and set before() filter.
     *
     * @param array $config   Configuration array, loaded from config.php
     * @param string $rootDir Local FS path to the app root (where composes.json is)
     */
    public function __construct($config, $rootDir, $hostname = null)
    {
        parent::__construct();

        $this['neoconfig'] = $config;
        $this['root_dir'] = $rootDir;

        //Trick for non-web scripts (e.g. testAllTranspositions)
        $this->hostname = $hostname ?: $_SERVER['HTTP_HOST'];

        $this->registerSilexServices($rootDir);
        $this->initializeSession();
        $this->registerErrorHandler();

        include __DIR__ . '/services.php';

        if (!empty($config['debug'])) {
            $this['debug'] = $config['debug'];
        }

        /*
         * Actions before every controller.
         */
        $this['controllers']->before(function (Request $request, Application $app) {

            if ($request->query->get('debug')) {
                $app['debug'] = true;
            }

            //$request->setTrustedProxies($app['neoconfig']['trusted_proxies']);
            $request->setTrustedProxies($app['neoconfig']['trusted_proxies'], Request::HEADER_X_FORWARDED_AWS_ELB);

            $app['twig']->addGlobal('current_route', $request->attributes->get('_route'));

            $app['absoluteUriWithoutQuery'] = $request->getScheme()
                . '://'
                . $request->getHttpHost()
                . strtok($request->getRequestUri(), '?');

            $app['absoluteBasePath'] = $request->getScheme()
                . '://'
                . $request->getHttpHost()
                . $request->getBasePath();
        });
    }

    /**
     * Sets the Locale based on country (geoIP) and, as a fallback, on the
     * request header Accept-Language. Though Accept-Language works reasonably
     * well, the NCW is organized by country, and where a country speaks a
     * language, the catechumens sing in that language (with a few exceptions,
     * like USA). This is why geoip language detection works better.
     *
     * The way getPreferredLanguage() works is by 'expanding' the array with the
     * 'only language' values, e.g. [es_ES, en_US] => [es_ES, es, en_US, en],
     * but if the 'only language' values are already present, leave them where
     * they are. This way, in a request like [es_ES, en_GB, en, es] 'en' will be
     * selected, because es_ES is not found in $app['neoconfig']['languages']
     * (because in NeoApp locales are defined only by languages). Such a tricky
     * case though, has only occurred in my Chrome/LineageOS for reasons unknown.
     *
     * @param Request $request  The HTTP request.
     */
    public function setLocaleAutodetect(Request $request): void
    {
        $this['locale'] = $request->getPreferredLanguage(
            array_keys($this['neoconfig']['languages'])
        );

        $ipToLocaleResolver = $this[IpToLocaleResolver::class];
        $this['locale'] = $ipToLocaleResolver->resolveIpToLocale($request->getClientIp()) ?? $this['locale'];
    }

    /**
     * Register some Silex services used in the app.
     *
     * @param string $rootDir Real FS path to app root, where the cache/ dir is.
     *
     * @see composer.json, since some of these services require ext dependencies.
     */
    private function registerSilexServices($rootDir): void
    {
        $twigOptions = null;
        if (!$this['debug']) {
            $twigOptions = ['cache' => $rootDir . '/cache/twig'];
        }

        $this->register(new \Silex\Provider\TwigServiceProvider(), [
            'twig.path' => $this['neoconfig']['templates_dir'],
            'twig.options' => array_merge($twigOptions, [
                'strict_variables' => false
            ])
        ]);

        $this->register(new \Silex\Provider\DoctrineServiceProvider(), [
            'db.options' => [
                'driver'    => 'pdo_mysql',
                'host'      => $this['neoconfig']['db']['host'],
                'user'      => $this['neoconfig']['db']['user'],
                'password'  => $this['neoconfig']['db']['password'],
                'dbname'    => $this['neoconfig']['db']['database'],
                'charset'   => $this['neoconfig']['db']['charset']
            ],
        ]);

        //Must be called before session_start()
        session_set_cookie_params(
            2_592_000,                      //Lifetime: 1 month
            '/; samesite=Lax',              //Path + samesite (see <https://www.php.net/manual/es/function.session-set-cookie-params.php#125072>)
            $this->hostname,                //Domain
            !(bool) $this['neoconfig']['debug'],   //Secure
            true                            //httponly
        );

        $this->register(new \Silex\Provider\SessionServiceProvider());
        $this->register(new \Silex\Provider\RoutingServiceProvider());

        $this->register(new \Silex\Provider\LocaleServiceProvider());
        $this->register(new \Silex\Provider\TranslationServiceProvider());
        $translations = [];
        foreach ($this['neoconfig']['languages'] as $locale => $details) {
            if (isset($details['file'])) {
                $translations['messages'][$locale] = include $details['file'];
            }
        }
        $this['translator.domains'] = $translations;
    }

    /**
     * Services available for every controller.
     */
    private function initializeSession(): void
    {
        if (!$this['session']->get('user')) {
            $this['session']->set('user', new User());
        }

        $this['neouser'] = $this['session']->get('user');
    }

    private function registerErrorHandler(): void
    {
        //Silex default error pages are better for debugging.
        if ($this['neoconfig']['debug']) {
            return;
        }

        $this->error(function (\Exception $e, Request $request, $code) {

            $this->setLocaleAutodetect($request);

            //For unknown reasons, translator falls back to English. This needed.
            $this['translator']->setLocale($this['locale']);

            if (in_array($code, [404, 500])) {
                return $this->render("error-$code.twig", ['error_code' => $code]);
            }

            $title = match (intdiv($code, 100)) {
                4 => $this->trans('Request error'),
                5 => $this->trans('Server error'),
                default => $this->trans('Unknown error'),
            };

            return $this->render('error.twig', [
                'error_code'        => $code,
                'error_title'       => $title,
                'error_description' => $this->trans('Error %code%', ['%code%' => $code])
            ]);
        });
    }

    /**
     * Adds a notification that will be shown in the app's header (see base.tpl)
     *
     * @param string $type 'error' or 'success'.
     * @param string $text Text of the notification.
     */
    public function addNotification($type, $text): void
    {
        if (!array_key_exists($type, $this->notifications)) {
            throw new \OutOfRangeException("Notification type $type not valid");
        }
        $this->notifications[$type][] = $text;
    }

    /**
     * Pre-processing of the template variables and render the template.
     *
     * @param  string $view       Template name
     * @param  array  $parameters Array variables.
     * @return string             The rendered template.
     */
    public function render($view, array $parameters = [], $modifyTitle = true): string
    {
        if ($modifyTitle) {
            $this->setPageTitle($parameters);
        }

        $parameters['neoapp_css_file'] = $this['debug']
            ? 'style.css?nocache=' . time()
            : 'compiled-' . $this['neoconfig']['css_cache'] . '.css';

        $this['twig']->addGlobal('notifications', $this->notifications);
        return $this['twig']->render($view, $parameters);
    }

    /**
     * Set final page title adding a certain suffix if the title specified in
     * the controller is not too long.
     *
     * @param array &$parameters Template variables (should contain page_title)
     */
    private function setPageTitle(array &$parameters): void
    {
        $suffix = $this->trans($this['neoconfig']['seo_title_suffix']);

        if (isset($parameters['page_title'])) {
            if (strlen((string) $parameters['page_title']) < self::PAGE_TITLE_MAX_LENGTH - strlen($suffix)) {
                $parameters['page_title'] .= " · $suffix";
            }
        } else {
            $parameters['page_title'] = $this['neoconfig']['software_name'];
        }
    }
}
