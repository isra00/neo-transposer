@extends('_base')

@section('content')

<h1>@lang('Choose language')</h1>

<form method="post" action="{{ route('set_user_data') }}">
    @csrf
    <ul class="books">
    @foreach ($books as $book)
        <li><button type="submit" name="book" value="{{ $book->idBook() }}">
            {{ $book->langName() }}
            <small>{{ $book->details() }}</small>
        </button></li>
    @endforeach
    </ul>
</form>

@endsection
