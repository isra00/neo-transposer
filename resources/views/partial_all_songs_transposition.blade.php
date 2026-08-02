@php
    if ($typeOfTransposition == 'centered1') {
        $other = false;
    } else {
        $other = ($item->feedbackTranspositionWhichWorked !== $typeOfTransposition);
    }
@endphp

<li class="trans-{{ $typeOfTransposition }} @if($other) other @endif">

    @if ($item->feedbackTranspositionWhichWorked == $typeOfTransposition)
        <span class="feedback green" title="@lang('You have reported this transposition as fitting your voice')">&#10004;</span>
    @else
        <span class="marker-when-multiple">&#8250;</span>
    @endif

    <span class="prefix">{{ $prefix }}</span>

    @if ($transposition->getCapo())
        <span class="capo">C{{ $transposition->getCapo() }}</span>
    @endif

    @if ($transposition->getAsBook())
        @lang('(as in the book)')
    @else
        @foreach ($item->transposedSong->song->originalChordsForPrint as $i => $original_chord)
            <span class="chord-pair">
                {!! $original_chord !!}
                &rarr;
                {!! $transposition->chordsForPrint[$i] !!}@if (!$loop->last); @endif
            </span>
        @endforeach
    @endif
</li>
