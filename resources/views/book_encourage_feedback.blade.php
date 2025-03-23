@extends('book')

@section('preContent')

    @if(session('user')->isLoggedIn())

        @if($user_performance->reports() == 0)
        <div class="encourage-feedback">
            <h2>
                @if(session('user')->firstTime)
                    <span>@lang('Last step:')</span>
                @endif
                <span>@lang('Verify with 2 songs that we have measured your voice accurately')</span>
            </h2>
            <p>@lang('Choose <strong>2</strong> songs from the list and try to sing them with the chords suited to your voice. Please, <strong>report whether or not it has worked</strong>, so that the system may improve your performance.')</p>
            <p class="advice">@lang('Your voice:') {!! $your_voice !!}</p>
        </div>

        @elseif($user_performance->reports() == 1)

            <div class="encourage-feedback img-left">
            @if($user_performance->score() < 1)
                <h2>@lang('Oops! Ok, let\'s try again')</h2>
                <p>@lang('Please, try another song, and report whether the chords have worked for you.')</p>
            @else
                <h2>@lang('Verify 1 more song')</h2>
                <p>@lang('Please, try one more, and <strong>report whether or not it has worked</strong> so that the system may improve your performance.')</p>
            @endif
            </div>

        @elseif($user_performance->reports() == 2 && session('user')->firstTime)

            @if($user_performance->score() == 0)
                <div class="encourage-feedback achieved-minimum-fb all-negative">
                    <h2>@lang('Well, this is a bit embarrassing')</h2>
                    <p>@lang('It seems like none of the songs you have reported has worked for you. Please try some more songs and if the problem still persists, I will propose you to change your voice range.')</p>
                </div>
            @elseif($user_performance->score() > 0 and $user_performance->score() < 1)
                <div class="encourage-feedback achieved-minimum-fb">
                    <h2>@lang('Nothing\'s perfect, but let\'s keep trying!')</h2>
                    <p>@lang('Well, it seems like one of the songs fits you, the other doesn\'t. You may try others, and I\'m sure they will go better!')</p>
                </div>
            @else
                <div class="encourage-feedback achieved-minimum-fb">
                    <h2>@lang('We\'re good to go!')</h2>
                    <p>@lang('All right! Those chords fit your voice like a charm. You may keep trying other songs and reporting feedback, whether it works or it doesn\'t. You\'re welcome!')</p>
                </div>
            @endif

        @endif
    @endif

@endsection
