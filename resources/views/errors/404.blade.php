@php $page_class = 'error-page'; @endphp
@extends('_base')

@section('content')

    <h1>@lang('Page not found')</h1>
    <h2>@lang('The address you have requested does not exist, or has been removed.')</h2>
    <p>&rarr; @lang('You may have clicked on a broken link, or perhaps you wrote the URL manually and made a mistake.')</p>
    <p>&rarr; {!! __('Try to go back to the <a href=":url">home page</a>.', ['url' => route('login', ['locale' => app()->getLocale()])]) !!}</p>

@endsection

@section('footer')@endsection
