@extends('_base')

@section('content')

<h1>@lang('Choose language')</h1>

<ul class="books">
@foreach ($books as $book)
    <li><a href="{{ route('set_user_data', ['book' => $book->idBook()]) }}">
        {{ $book->langName() }}
        <small>{{ $book->details() }} ({{ trans_choice('{1} :count song|[2,*] :count songs', $book->songCount()) }})</small>
    </a></li>
@endforeach
</ul>

@endsection
