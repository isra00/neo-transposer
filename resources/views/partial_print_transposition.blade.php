<table @class(['transposition', 'as-book' => $transposition->getAsBook()])>
    <thead>
    <tr>
        <th @if(!$transposition->getAsBook()) colspan="{{ config('app.debug') ? 4 : 3 }}" @endif>
            {!! $transposition->chordsForPrint[0] !!}
            <span class="capo">{!! $transposition->getCapoForPrint() !!}</span>
            @if(config('app.debug'))
                <small class="score">[{{ round($transposition->score) }}]</small>
            @endif
        </th>
    </tr>
    </thead>
    <tbody>
    @if(!$transposition->getAsBook())
        @foreach($original_chords as $chord)
            <tr>
                <td class="original">{!! $chord !!}</td>
                <td class="arrow center">&rarr;</td>
                @if(config('app.debug'))
                    <td class="chord-score">
                        {{ $transposition->scoreMap[$transposition->chords[$loop->index]->__toString()] }}
                    </td>
                @endif
                <td class="transposed" data-chord="{{ $transposition->chords[$loop->index] }}">
                    {!! $transposition->chordsForPrint[$loop->index] !!}
                </td>
            </tr>
        @endforeach
    @else
        <tr><td class="as-book">@lang('(same chords as in the book)')</td></tr>
    @endif
    </tbody>
</table>
