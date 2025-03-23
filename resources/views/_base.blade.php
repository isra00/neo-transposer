<!doctype html>
<html translate="no" lang="{{ app()->getLocale() }}">
<head>

    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $page_title }}</title>
    <link rel="stylesheet" href="{{ url('/static/style.css?v=' . $cssVersion) }}" type="text/css" />

    <link rel="icon" type="image/svg+xml" sizes="512x512" href="{{ url('/static/img/logo-red.svg') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ url('/static/img/icon-512x512.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url('/static/img/icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="180x180" href="{{ url('/static/img/apple-touch-icon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/static/img/apple-touch-icon.png') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}">

    <link rel="manifest" href="{{ route('webmanifest', ['locale' => app()->getLocale()]) }}">

    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $page_title }}">
    <meta property="og:description" content="{{ $meta_description ?? __('Neo-Transposer automatically transposes the songs of the Neocatechumenal Way for you, so they fit your voice perfectly.') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ url('/static/img/mkt-' . app()->getLocale() . '-600x315.jpg') }}">
    <meta property="og:locale" content="{{ app()->getLocale() }}">

    <meta name="google" content="notranslate" />
    <meta name="description" content="{{ $meta_description ?? __('Neo-Transposer automatically transposes the songs of the Neocatechumenal Way for you, so they fit your voice perfectly.') }}" />

    @if(isset($meta_canonical))
    <link rel="canonical" href="{{ $meta_canonical }}" />
    @endif

    @if(config('app.debug') || request()->header('dnt'))
    <script>var gtag = function() {}</script>
    @else
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics_id') }}"></script>
    <script>
        (function () {
            window.colorSchemePref = 'No Preference';
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                window.colorSchemePref = 'Dark';
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                window.colorSchemePref = 'Light';
            }
        })();

        let dimensions = {
            colorSchemePref: window.colorSchemePref,
            external: '{{ request()->get('external') }}'
        };

        @if(session('user')->isLoggedIn())
        dimensions.lowestNote = '{{ Auth::user()->range['lowest'] ?? '-' }}';
        dimensions.highestNote = '{{ Auth::user()->range['highest'] ?? '-' }}';
        dimensions.user_id = '{{ Auth::id() }}';
        @endif

        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.analytics_id') }}', dimensions);
        gtag('set', 'content_group', '{{ app()->getLocale() }}');
    </script>
    @endif

    @yield('header_extra')
</head>

<body class="lang-{{ app()->getLocale() }} {{ $page_class ?? '' }}" id="top">

<div class="wrapper">

    @yield('languageSwitchTop')

    @section('header')
    <nav class="header">
        <div class="inside">
            @if(isset($header_link))
            <h2>
                <a href="{{ $header_link }}">{{ config('app.name') }}</a>
            </h2>
            @else
            @if(Route::currentRouteName() == 'login')
            <h1>{{ config('app.name') }}</h1>
            @else
            <h2>{{ config('app.name') }}</h2>
            @endif
            @endif

            @if(session('user')->isLoggedIn() && Route::currentRouteName() != 'login')
            <span class="user">
                    <a href="{{ route('login', ['locale' => app()->getLocale()], false) }}">@lang('Log-out')</a>
                </span>
            @endif
        </div>
    </nav>
    @show

    <section class="main">

        @if (session('error'))
        <div class="notification error">{{ session('error') }}</div>
        @endif

        @if (session('success'))
        <div class="notification success">{{ session('success') }}</div>
        @endif

        @yield('content')
    </section>

    <div class="push"></div>
</div>

@section('footer')
<footer>
    @lang('Developed as <a href=":url">free software</a> in Tanzania.', ['url' => 'https://github.com/isra00/neo-transposer'])
    <a href="mailto:neo-transposer@mail.com">@lang('Contact')</a>.
</footer>
@show

@hasSection('scripts')
<script src="{{ url('/') }}/static/zepto.min.js"></script>
@yield('scripts')
@endif

</body>
</html>
