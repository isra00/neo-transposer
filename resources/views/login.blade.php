@extends('_base')

@section('header_extra')
    @if (!config('app.debug') && !config('neoconfig.disable_recaptcha'))
        <script src='https://www.google.com/recaptcha/api.js'></script>
    @endif

    @foreach ($all_books as $id => $book)
        <link rel="alternate" hreflang="{{ $book->locale() }}" href="{{ url()->to(route('login', ['locale' => $book->locale()])) }}" />
    @endforeach
@endsection

@section('page_class', '')

@section('languageSwitchTop')
    @if (!request()->has('external'))
        <nav class="lang-switch-top">
            @foreach (config('nt.languages') as $locale => $lang)
                <a class="flag-{{ $locale }} @if ($locale == app()->getLocale()) active @endif" hreflang="{{ $locale }}" href="{{ route('login', ['locale' => $locale]) }}">
                    {{ Str::substr($lang['name'], 0, 3) }}<span>{{ Str::substr($lang['name'], 3) }}</span>
                </a>
            @endforeach
        </nav>
    @endif
@endsection

@section('content')
    <div class="mkt" id="up">
        <h2>
            <span class="inside">
                @lang('<span>It\'s not hard to transpose a song. What is hard is</span> <span>to know to which key should I transpose it <strong>for my voice</strong>.</span>')
            </span>
        </h2>
    </div>

    <div class="inside">
        <p class="mkt-text">@lang('Neo-Transposer calculates the perfect transposition for each song of the Way based on your own voice. That simple. It also offers you alternatives to play the song with the easiest chords. No more complications!')</p>

        @include('partial_login_form', ['error_msg' => $error_msg ?? ''])

        <section class="landing-info">
            <h3>@lang('How does it work?')</h3>

            <p class="with-number"><span><i>1</i></span> @lang('First, you measure the limits of your voice by singing the chorus of a sing in different keys. It\'s a bit tiresome, but don\'t worry, you\'ll have to do it just once.')</p>
            <p class="with-number"><span><i>2</i></span> @lang('After measuring your voice, choose any song and Neo-Transposer will calculate the chords and capo the fit you most.')</p>

            {{--
            <p>Cada persona tiene un tono de voz, más agudo o más grave. A
                menudo los acordes de los cantos del Camino según el libro de
                cantos determinan un tono de la canción demasiado agudo o
                demasiado grave para la voz del cantor. En ese caso solemos
                transportar los cantos, pero no es fácil hacerlo: ¿a qué tono
                debo transportarlo para mi voz? ¿Y la cejilla (capo), en qué
                traste se debe poner?</p>

            <p>Neo-Transposer te ayuda a resolver este problema. La aplicación
                mide tu voz y calcula los acordes óptimos para tu voz.
                Inscríbete con tu e-mail, sigue los pasos y en unos minutos
                tendrás los acordes de todos los cantos transportados a tu
                voz.</p>

            <h4>Usa Neo-Transposer, ¡pero usa también la cabeza! :-)</h4>

            <p>Ojo, ¡esto es solo una herramienta! El carisma de cantor es
                mucho más, y exige práctica, mucha práctica. Debes tener en
                cuenta la intención de cada canto, para cantarlo con un tono
                de voz que no solo sea bueno para ti, sino que transmita bien
                la intención del canto y ayude a cantar a toda la comunidad. Si
                tienes dudas, pregunta siempre a otros cantores y a tus
                catequistas.</p>

            <p class="center margintop">
                <a href="#up" id="cta" class="btn-neutral bigbutton">Entrar en Neo-Transposer</a>
            </p>
            --}}

            <p class="disclaimer">@lang('This website is a personal initiative of a member of the Neocatechumenal Way, but it does not officially represent neither the Neocatechumenal Way nor its responsibles.')</p>
        </section>
    </div>
@endsection
