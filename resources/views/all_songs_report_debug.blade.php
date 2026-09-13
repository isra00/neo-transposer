@extends('all_songs_report')

@section('songsList')
<ul class="songs-list debug">
@foreach ($all_songs_transposed_with_fb as $item)
    <li>
        <h2>
            <span class="page-number">{!! $item->transposedSong->song->page ?? '&#248;' !!} · </span>
            {{ $item->transposedSong->song->title }}
            @if(config('app.debug')) <a href="{{ route('transpose_song', ['id_song' => $item->transposedSong->song->idSong]) }}">[{{ $item->transposedSong->song->idSong }}]</a>@endif
        </h2>

        <ul>
            @foreach ($item->transposedSong->transpositionsCentered as $idx => $transposition)
                <li>
                    <strong>centered{{ $idx + 1 }}</strong>
                    @if ($transposition->getCapo())
                        <span class="capo">C{{ $transposition->getCapo() }}</span>
                    @endif
                    @if ($transposition->getAsBook())
                        @lang('(as in the book)')
                    @else
                        @foreach ($item->transposedSong->song->originalChordsForPrint as $i => $original_chord)
                            <span class="chord-pair">{!! $original_chord !!} &rarr; {!! $transposition->chordsForPrint[$i] !!}@if (!$loop->last); @endif</span>
                        @endforeach
                    @endif
                </li>
            @endforeach

            @if ($item->transposedSong->transpositionEasierNotEquivalent)
                <li>
                    <strong>{{ $item->transposedSong->transpositionEasierNotEquivalent->deviationFromCentered > 0 ? '+' : '' }}{{ $item->transposedSong->transpositionEasierNotEquivalent->deviationFromCentered }} notEquivalent</strong>
                    @if ($item->transposedSong->transpositionEasierNotEquivalent->getCapo())
                        <span class="capo">C{{ $item->transposedSong->transpositionEasierNotEquivalent->getCapo() }}</span>
                    @endif
                    @foreach ($item->transposedSong->song->originalChordsForPrint as $i => $original_chord)
                        <span class="chord-pair">{!! $original_chord !!} &rarr; {!! $item->transposedSong->transpositionEasierNotEquivalent->chordsForPrint[$i] !!}@if (!$loop->last); @endif</span>
                    @endforeach
                </li>
            @endif

            @if ($item->transposedSong->getPeopleCompatibleStatus())
                <li>
                    <strong><em>{{ $item->transposedSong->getPeopleCompatible() ? (($item->transposedSong->getPeopleCompatible()->deviationFromCentered > 0 ? '+' : '') . $item->transposedSong->getPeopleCompatible()->deviationFromCentered . ' pc ') : '' }}{{ $item->transposedSong->getPeopleCompatibleStatusMsg() }}</em></strong>
                    @if ($item->transposedSong->getPeopleCompatible())
                        @if ($item->transposedSong->getPeopleCompatible()->getCapo())
                            <span class="capo">C{{ $item->transposedSong->getPeopleCompatible()->getCapo() }}</span>
                        @endif
                        @foreach ($item->transposedSong->song->originalChordsForPrint as $i => $original_chord)
                            <span class="chord-pair">{!! $original_chord !!} &rarr; {!! $item->transposedSong->getPeopleCompatible()->chordsForPrint[$i] !!}@if (!$loop->last); @endif</span>
                        @endforeach
                    @endif
                </li>
            @endif
        </ul>
    </li>
@endforeach
</ul>
@endsection
