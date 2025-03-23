@extends('_base')

@section('header_extra')
    @if (!session('user')->isLoggedIn() && !config('app.neoconfig.disable_recaptcha'))
        <script src='https://www.google.com/recaptcha/api.js'></script>
    @endif
@endsection

@section('page_class', 'transpose-song')

@section('content')
    @if (!session('user')->isLoggedIn())
        <div class="teaser">
            <div class="inside">
                <div class="more-inside">
                    <p>@lang('Neo-Transposer helps you to automatically transpose the chords of <strong>:song</strong> so they match your voice. Type your e-mail, follow the steps and it will transpose all the songs of the Neocatechumenal Way for you!', ['song' => $song->song->title])</p>
                    @include('partial_login_form', ['redirect' => request()->getRequestUri()])
                </div>
            </div>
        </div>
    @endif

    <h1>
        {{ $song->song->title }}
        <small>@if(config('app.debug')) [{{ $song->song->idSong }}] @endif</small>
    </h1>

    @if ($song->song->url)
        <h3 class="song-url">
            <a href="{{ $song->song->url }}" target="_blank">
                @lang('Official audio and lyrics')
                <span>
                    <svg width="auto" height="100%" viewBox="0 0 1.5629659 1.5629659" id="svg1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"><g id="Lager_80" data-name="Lager 80" transform="matrix(0.04883963,0,0,0.04883963,0,9.7839678e-5)"><path id="Path_89" data-name="Path 89" d="m 24,22 v 5 a 1,1 0 0 1 -1,1 H 5 A 1,1 0 0 1 4,27 V 9 A 1,1 0 0 1 5,8 h 5 A 2,2 0 0 0 12,6 V 6 A 2,2 0 0 0 10,4 H 3 A 3,3 0 0 0 0,7 v 22 a 3,3 0 0 0 3,3 h 22 a 3,3 0 0 0 3,-3 v -7 a 2,2 0 0 0 -2,-2 v 0 a 2,2 0 0 0 -2,2 z" fill="CurrentColor" /><rect id="Rectangle_40" data-name="Rectangle 40" width="16" height="4" rx="2" transform="translate(16)" fill="CurrentColor" x="0" y="0" /><rect id="Rectangle_41" data-name="Rectangle 41" width="16" height="4" rx="2" transform="rotate(90,16,16)" fill="CurrentColor" x="0" y="0" /><g id="Group_37" data-name="Group 37"><rect id="Rectangle_42" data-name="Rectangle 42" width="32.296001" height="3.971" rx="1.9859999" transform="rotate(-45,30.162249,2.3423875)" fill="CurrentColor" x="0" y="0" /></g></g></svg>
                </span>
            </a>
        </h3>
    @endif

    <p class="explanation">@lang('These two transpositions match your voice (they are equivalent). The first one has easier chords:')</p>

    <div class="transpositions-list">
        @foreach ($song->transpositions as $transposition)
            @include('partial_print_transposition', ['transposition' => $transposition, 'original_chords' => $song->song->originalChordsForPrint])
        @endforeach
    </div>

    @if ($song->not_equivalent)
        @php $difference = ($song->not_equivalent->deviationFromCentered > 0) ? __('higher') : __('lower') @endphp
        <p class="explanation">@lang('This other transposition is a bit :difference, but it has easier chords and may also fit your voice:', ['difference' => $difference])</p>
        <div class="transpositions-list">
            @include('partial_print_transposition', ['transposition' => $song->not_equivalent, 'original_chords' => $song->song->originalChordsForPrint])
        </div>
    @endif

    @if ($song->getPeopleCompatible())
        <p class="explanation people-compatible-info {{ $peopleCompatibleClass ?? '' }}">
            {{ $peopleCompatibleMsg }}
            <small><a class="inline-block" href="{{ route('people-compatible-info', ['locale' => app()->getLocale()]) }}">@lang('Learn more')</a></small>
        </p>
        <div class="transpositions-list">
            @include('partial_print_transposition', ['transposition' => $song->getPeopleCompatible(), 'original_chords' => $song->song->originalChordsForPrint])
        </div>
    @endif

    @if ($song->isAlreadyPeopleCompatible())
        <p class="tip people-compatible-info star">
            @lang('With these chords the assembly too will be able to sing the song comfortably.')
            <small><a href="{{ url('people-compatible-info', ['locale' => app()->getLocale()]) }}">@lang('Learn more')</a></small>
        </p>
    @endif

    @if (config('app.debug') && $song->getPeopleCompatibleStatus())
        <p class="tip no-icon"><big style="font-size: 2em">💭</big> {{ $song->getPeopleCompatibleStatusMsg() }}</p>
    @endif

    <a name="feedback"></a>

    @if (session('user')->isLoggedIn())
        <form class="transposition-feedback" method="post" action="{{ route('transposition_feedback') }}">
            @csrf
            <input type="hidden" name="id_song" value="{{ $song->song->idSong }}">
            <input type="hidden" name="centered_score_rate" value="{{ $song->transpositions[1]->score / $song->transpositions[0]->score }}">
            <input type="hidden" name="deviation" value="{{ $song->not_equivalent->deviationFromCentered ?? $song->getPeopleCompatible()->deviationFromCentered ?? null }}">
            <input type="hidden" name="pc_status" value="{{ $song->getPeopleCompatibleStatusMsg() ?? '' }}">

            <p class="answers" @if($non_js_fb) style="display: none" @endif>
                <button type="submit" name="worked" value="1" class="yes @if($feedback == 'yes') highlighted @endif" id="feedback-yes" @if($feedback == 'yes') title="@lang('You have reported the proposed transposition as valid')" @endif data-worked="1">
                    @lang('Yes') @if($feedback == 'yes') &#10004; @endif
                    <small>@lang('It has worked')</small>
                </button>
                <button type="submit" name="worked" value="0" class="no @if($feedback == 'no') highlighted @endif @if($feedback == 'yes') lowlighted @endif" id="feedback-no" data-worked="0">
                    @lang('No')
                    <small>@lang('It hasn\'t worked')</small>
                </button>
            </p>

            <div class="thanks @if($non_js_fb != 'yes') hidden @endif" id="feedback-thanks">@lang('Happy to know that! :-)')</div>

            <ul id="reasons-no" class="@if($non_js_fb != 'no') hidden @endif">
                @if ($user_less_than_one_octave)
                    <li class="big">@lang('It seems you have not measured your voice properly. Please, <a href=":url">follow this instructions</a>.', ['url' => $url_wizard . "#afterNegativeFeedbackWithBadVoice"])</li>
                @else
                    <li>@lang('Maybe you didn\'t measure your voice properly. <a href=":url">Click here to go to the Wizard</a>.', ['url' => $url_wizard . "#afterNegativeFeedback"])</li>
                    <li>@lang('Maybe you are not singing the song the same way it was analysed for the application')</li>
                    <li>@lang('Maybe you are not singing in the same tone as the guitar')</li>
                @endif
            </ul>
        </form>
    @endif

    <div class="your-voice">
        <em>@lang('Your voice:')</em> {!! $your_voice !!}
        <a href="{{ route('user_voice', ['locale' => app()->getLocale(), 'redirect' => request()->getRequestUri()]) }}" class="small-button">@lang('Change')</a>
    </div>

    <p class="show-voice-chart">
        <a href="javascript:void(0)" class="btn-neutral" id="show-voice-chart">@lang('Show voice chart')</a>
    </p>

    <div id="voicechart-container">
        <table class="voicechart">
            <col style="width: 5rem">
            <tbody>
            @foreach ($voice_chart as $voice)
                <tr class="{{ $voice['css'] }}">
                    <th>{{ __($voice['caption']) }}</th>
                    @for ($i = 0; $i < $voice['offset']; $i++)
                        <td>&nbsp;</td>
                    @endfor
                    <td class="note">
                        <div class="inside">
                            <div class="note-in-chart">{{ $voice['lowestForPrint'] }}</div>
                        </div>
                    </td>
                    @for ($i = 0; $i < $voice['length'] - 1; $i++)
                        <td><div class="colored">&#11035;</div></td>
                    @endfor
                    <td class="note">
                        <div class="inside">
                            <div class="note-in-chart">{{ $voice['highestForPrint'] }}</div>
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr><th><a href="javascript:void(0)" id="chart-more">@lang('More')</a></th></tr>
            </tbody>
        </table>
    </div>

    <div id="dark-bg"></div>
    <div id="chord-chart-dialog">
        <figure>
            <img id="chord-chart" src="" alt="" data-urlpattern="{{ request()->getBasePath() }}/static/img/chords/@.png">
            <figcaption id="chord-chart-caption"></figcaption>
        </figure>
    </div>

    @if (session('user')->isLoggedIn())
        @if (config('nt.detailed_feedback'))
            <div id="detailed-feedback-container">
                <div id="detailed-feedback-dialog">
                    <h4>@lang('Which one has worked for you?')</h4>
                    <ul id="transpositions-feedback">
                        @foreach ($song->transpositions as $trans)
                            <li>
                                <a href="javascript:void(0)" class="detailed-fb-choice" data-transposition="centered{{ $loop->iteration }}" data-deviation="">
                                    <span class="chord-sans">{!! $trans->chordsForPrint[0] !!}</span>
                                    <span class="capo">{{ $trans->getCapoForPrint() }}</span>
                                    <span class="circle"></span>
                                </a>
                            </li>
                        @endforeach

                        @if ($song->not_equivalent)
                            <li>
                                <a href="javascript:void(0)" class="detailed-fb-choice" data-transposition="notEquivalent" data-deviation="{{ $song->not_equivalent->deviationFromCentered }}">
                                    <span class="chord-sans">{!! $song->not_equivalent->chordsForPrint[0] !!}</span>
                                    <span class="capo">{{ $song->not_equivalent->getCapoForPrint() }}</span>
                                    <small>★ {{ $difference }}</small>
                                    <span class="circle"></span>
                                </a>
                            </li>
                        @endif

                        @if ($song->getPeopleCompatible())
                            <li>
                                <a href="javascript:void(0)" class="detailed-fb-choice" data-transposition="peopleCompatible" data-deviation="{{ $song->getPeopleCompatible()->deviationFromCentered }}">
                                    <span class="chord-sans">{!! $song->getPeopleCompatible()->chordsForPrint[0] !!}</span>
                                    <span class="capo">{{ $song->getPeopleCompatible()->getCapoForPrint() }}</span>
                                    <small>★ @lang('assembly')</small>
                                    <span class="circle"></span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    @endif
@endsection

@section('scripts')
    @if (session('user')->isLoggedIn())
        <script>
            NT = {
                showChart: function(oLinkContainer) {
                    document.getElementById("voicechart-container").style.display = 'block';
                    $(oLinkContainer).remove();
                    gtag('event', 'ShowVoiceChart', {'event_category': 'Actions', 'event_label': '{{ $song->song->title }}'});
                },

                sendFeedbackAjax: function(postJsonData, callbackSuccess) {
                    $.ajax({
                        url: "{{ route('transposition_feedback', [], false) }}",
                        type: "POST",
                        dataType: "json",
                        data: postJsonData,
                        success: callbackSuccess,
                        error: function(XHR) {
                            if (XHR.status === 408) {
                                document.getElementById("feedback-thanks").style.display = 'none';
                                gtag('event', 'FeedbackSentAfterSessionExpired', {'event_category': 'FeedbackTransposition', 'event_label': '{{ $song->song->title }}'});

                                alert("@lang('Your session has expired. Please log-in and submit the feedback again.')");
                                window.location.reload();
                            }
                        }
                    });
                },

                sendBasicFeedback: function(iAnswer) {
                    NT.sendFeedbackAjax({
                        id_song: {{ $song->song->idSong }},
                        worked: iAnswer,
                        referer: '{{ request()->server('HTTP_REFERER') }}',
                        centered_score_rate: '{{ $song->transpositions[1]->score / $song->transpositions[0]->score }}',
                        deviation: '{{ $song->not_equivalent?->deviationFromCentered ?: $song->getPeopleCompatible()?->deviationFromCentered ?: null }}',
                        pc_status: '{{ $song->getPeopleCompatibleStatusMsg() ?? '' }}',
                    }, function() {
                        gtag('event', (iAnswer ? 'Worked' : 'NotWorked'), {'event_category': 'FeedbackTransposition', 'event_label': '{{ $song->song->title }}'});

                        @if (config('nt.detailed_feedback'))
                        if (iAnswer === 1) {
                            NT.showDetailedFeedbackDialog();
                        }
                        @endif
                    });
                },

                sendDetailedFeedback: function(transposition, deviation) {
                    NT.sendFeedbackAjax({
                        id_song: {{ $song->song->idSong }},
                        worked: 1,
                        referer: '{{ request()->server('HTTP_REFERER') }}',
                        transposition: transposition,
                        pc_status: '{{ $song->getPeopleCompatibleStatusMsg() }}',
                        deviation: deviation,
                        centered_score_rate: {{ $song->transpositions[1]->score / $song->transpositions[0]->score }}
                    });
                },

                showDetailedFeedbackDialog: function() {
                    document.getElementById("dark-bg").style.display = 'block';
                    document.getElementById("detailed-feedback-container").style.display = 'block';

                    $(".detailed-fb-choice").click(function(e) {
                        NT.sendDetailedFeedback(
                            e.currentTarget.dataset.transposition,
                            e.currentTarget.dataset.deviation
                        );

                        document.getElementById("dark-bg").style.display = 'none';
                        document.getElementById("detailed-feedback-container").style.display = 'none';
                        $(".answers").hide();
                        document.getElementById("feedback-thanks").style.display = 'block';
                    });
                },

                openChordChartDialog: function(event) {
                    var hTarget = $(event.target).closest("td.transposed")[0],
                        chordName = hTarget.dataset.chord,
                        chordChartImg = document.getElementById('chord-chart'),
                        dialog = document.getElementById('chord-chart-dialog'),
                        chordChartCaption = document.getElementById('chord-chart-caption'),
                        isTouchScreen = window.navigator.msMaxTouchPoints || ('ontouchstart' in document.documentElement),
                        darkBackground = document.getElementById('dark-bg');

                    //5th chords are wrong in the ES songbook. They are actually 9th -_-
                    chordName = chordName.replace(/5$/, '9');

                    chordChartImg.addEventListener('error', function(event) {
                        gtag('event', 'RequestMissingChordChart', {'event_category': 'Actions', 'chord_missing_chart': chordName});
                        event.target.src = '{{ request()->root() }}/static/img/chords/unknown-{{ app()->getLocale() }}.png';
                    });

                    chordChartImg.setAttribute(
                        'src',
                        chordChartImg.dataset.urlpattern.replace('@', chordName.replace('#', 's'))
                    );

                    chordChartImg.setAttribute('alt', hTarget.innerHTML);
                    chordChartImg.addEventListener('load', function() {
                        chordChartImg.style.visibility = 'visible';
                        dialog.style.width = chordChartImg.offsetWidth + 'px';
                    });

                    chordChartCaption.innerHTML = hTarget.innerHTML;

                    if (isTouchScreen) {
                        dialog.style.display = 'block';
                        darkBackground.style.display = 'block';
                        return;
                    }

                    // Position the dialog next to the chord.
                    var dialogTop = hTarget.getBoundingClientRect().top,
                        dialogLeft = hTarget.getBoundingClientRect().left + hTarget.getBoundingClientRect().width;

                    dialog.style.position = 'fixed';
                    dialog.style.margin = '0';
                    dialog.style.top = dialogTop + "px";
                    dialog.style.left = dialogLeft + "px";

                    dialog.style.display = 'block';

                    // If it gets out of the limits of the screen, place at in the corner.
                    /** @todo There should be a way of shortening this */
                    var currentRight = dialog.getBoundingClientRect().left + dialog.offsetWidth,
                        currentBottom = dialog.getBoundingClientRect().top + dialog.offsetHeight;

                    if (currentRight > window.innerWidth) {
                        dialog.style.left = (window.innerWidth - dialog.offsetWidth) + "px";
                    }

                    if (currentBottom > window.innerHeight) {
                        dialog.style.top = (window.innerHeight - dialog.offsetHeight) + "px";
                    }

                    event.target.addEventListener('mouseout', function() {
                        dialog.style.display = 'none';
                        chordChartImg.src = ''; //Avoid showing the previous one while loading
                        chordChartImg.style.visibility = 'hidden';
                    });
                },

                initializeChordChartDialog: function() {
                    var isTouchScreen = window.navigator.msMaxTouchPoints || ('ontouchstart' in document.documentElement),
                        dialog = document.getElementById('chord-chart-dialog'),
                        darkBackground = document.getElementById('dark-bg');

                    $(".transposed span").click(NT.openChordChartDialog);

                    if (!isTouchScreen) {
                        $(".transposed span").mouseover(NT.openChordChartDialog);
                    }

                    var closeDialog = function() {
                        dialog.style.display = 'none';
                        darkBackground.style.display = 'none';
                        document.getElementById("detailed-feedback-container").style.display = 'none';
                    };

                    dialog.addEventListener('click', closeDialog);
                    darkBackground.addEventListener('click', closeDialog);
                },

                init: function() {
                    NT.initializeChordChartDialog();

                    $(document.getElementById("show-voice-chart")).click(function(e) {
                        NT.showChart(e.target.parentNode);
                    });

                    $(document.getElementById("chart-more")).click(function(e) {
                        $(".original-people")
                            .add(".transposed-people")
                            .add(".people-standard")
                            .css("display", "table-row");
                        $(e.target.parentNode.parentNode).remove();
                    });
                }
            };

            $(function() {
                NT.init();

                $("#feedback-yes").add("#feedback-no").click(function(e) {
                    e.preventDefault();
                    $(".answers").hide();
                    var iAnswer = parseInt(this.dataset.worked);
                    NT.sendBasicFeedback(iAnswer);
                    document.getElementById(iAnswer ? "feedback-thanks" : "reasons-no").style.display = 'block';
                });
            });
        </script>
    @endif
@endsection
