@extends('_base')

@section('header_extra')
    @if ($print_css_code)
        <style>{!! $print_css_code !!}</style>
    @else
        <link rel="stylesheet" href="{{ url('/static/print.css') }}" type="text/css" media="print" />
    @endif
@endsection

@section('content')

<h1>@lang('All transpositions for your voice')</h1>

<p class="your-voice">
    <em>@lang('Your voice:')</em>
    {!! $your_voice !!}
</p>

<nav class="report-actions">
    <a href="javascript:void(0)" class="btn-neutral btn-icon icon-print">@lang('Print/PDF')</a>
    <a href="{{ route('all_songs_report', ['locale' => app()->getLocale(), 'dl' => 1]) }}" class="btn-neutral btn-icon icon-download">@lang('Download')</a>
</nav>

<nav class="toggle-transpositions">
    <span><input type="checkbox" id="show-others" onclick="toggle()"><label for="show-others">@lang('Show alternative transpositions')</label></span>
    @if(config('nt.show_people_compatible_in_report'))
    <span><input type="checkbox" id="show-people-compatible" onclick="togglePeopleCompatible()"><label for="show-people-compatible">@lang('Show people-compatible transpositions')</label></span>
    @endif
</nav>

<p class="note">@lang('C = capo. If not written, no capo should be used.')</p>

<div>
    @section('songsList')
    <ul class="songs-list">
    @foreach ($all_songs_transposed_with_fb as $item)
        <li>
            <h2>
                <span class="page-number">{!! $item->transposedSong->song->page ?? '&#248;' !!} · </span>
                {{ $item->transposedSong->song->title }}
                @if(config('app.debug')) <a href="{{ route('transpose_song', ['id_song' => $item->transposedSong->song->idSong]) }}">[{{ $item->transposedSong->song->idSong }}]</a>@endif
            </h2>

            <ul>
            @foreach (array_slice($item->transposedSong->transpositions, 0, 2) as $idx => $transposition)
                @include('partial_all_songs_transposition', [
                    'transposition' => $transposition,
                    'item' => $item,
                    'prefix' => null,
                    'typeOfTransposition' => 'centered' . ($idx + 1),
                ])
            @endforeach

            @if ($item->feedbackTranspositionWhichWorked === 'notEquivalent' && $item->transposedSong->not_equivalent)
                @include('partial_all_songs_transposition', [
                    'transposition' => $item->transposedSong->not_equivalent,
                    'item' => $item,
                    'prefix' => null,
                    'typeOfTransposition' => 'notEquivalent',
                ])
            @endif

            @if (config('nt.show_people_compatible_in_report') && $item->transposedSong->getPeopleCompatible())
                @include('partial_all_songs_transposition', [
                    'transposition' => $item->transposedSong->getPeopleCompatible(),
                    'item' => $item,
                    'prefix' => '[' . __('Assembly') . $item->peopleCompatibleStatusMicroMsg . ']',
                    'typeOfTransposition' => 'peopleCompatible',
                ])
            @endif
            </ul>
        </li>
    @endforeach
    </ul>
    @show
</div>

<script>
function toggle() {
    var aAllElements = document.getElementsByClassName("other");
    document.querySelector(".songs-list").classList.toggle("showing-others")
    for (e in aAllElements) {
        if (typeof aAllElements[e].classList != "undefined")
            aAllElements[e].classList.toggle("block");
    }
}

function togglePeopleCompatible() {
    var elements = document.getElementsByClassName("trans-peopleCompatible");
    document.querySelector(".songs-list").classList.toggle("showing-people-compatible");
    for (var e in elements) {
        if (typeof elements[e].classList != "undefined")
            elements[e].classList.toggle("block");
    }
}
</script>

@endsection

@section('scripts')
<script>
$(function() {

    if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1 && navigator.userAgent.toLowerCase().indexOf("android") > -1) {
        $(".icon-print").hide();
    }

    $(".icon-print").click(function() {
        gtag('event', 'Print', {'event_category': 'AllSongsReport'});
        window.print();
    });

    $(".icon-download")
        .attr("href", "javascript:void(0)")
        .click(function() {
            location.href = "{{ route('all_songs_report', ['locale' => app()->getLocale(), 'dl' => 1]) }}";
            gtag('event', 'Download', {'event_category': 'AllSongsReport'});
        });

    if (document.getElementById("show-others").checked) {
        toggle();
    }
});
</script>
@endsection
