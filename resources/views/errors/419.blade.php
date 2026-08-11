@php $page_class = 'error-page'; @endphp
@extends('_base')

@section('content')

    <h1>@lang('Your session has expired')</h1>
    <h2>@lang('The page was open for too long, so the form was not submitted.')</h2>
    <p>&rarr; {!! __('Try to go back to the <a href=":url">home page</a>.', ['url' => route('login', ['locale' => app()->getLocale()])]) !!}</p>

@endsection

@section('footer')@endsection
