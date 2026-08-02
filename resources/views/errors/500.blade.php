@php $page_class = 'error-page'; @endphp
@extends('_base')

@section('content')

    <h1>@lang('Internal error')</h1>
    <h2>@lang('Sorry! It\'s not your fault, but Neo-Transposer has just failed internally.')</h2>
    <p class="center">@lang('The administrator has been notified and will try to solve this as soon as possible.')</p>

@endsection

@section('footer')@endsection
