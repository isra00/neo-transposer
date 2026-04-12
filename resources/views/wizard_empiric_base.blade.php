@extends('_base')

@section('content')

	<nav class="wizard-nav">
		<div><a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}">&larr; @lang('Re-start')</a></div>
		<div><a href="{{ route('book_' . session('user')->id_book) }}">@lang('Exit Voice Test') &#10005;</a></div>
	</nav>

	<h1>@yield('title')</h1>

	<div class="test-area">

		<p class="instructions">@yield('instructions')</p>

		<div class="pre-song">
			@if($show_audio)
			<a href="javascript:void(0)" class="sound" id="sound">
				<span id="play-control" class="play-control stopped"><i>&nbsp;</i></span>
				@lang('Sing it like this:')
				<audio src="{{ url($audio_file) }}"></audio>
			</a>
			@endif
			<div class="inside">
				<h5>{{ $song_title }}</h5>
				<strong class="capo blink">{{ $song_capo }}</strong>
				<br><br>
				{!! $song !!}
			</div>
		</div>

		<form class="answer" method="post" action="{{ url()->current() }}" id="form-answer">
			<h3>@lang('Could you sing it?')</h3>

			<nav>
			@yield('answer_buttons')
			</nav>
		</form>
	</div>

	@yield('messages')

@endsection

@section('scripts')
	@if($show_audio)
		<script>

		var NTSound = {

			initializeSoundControl: function()
			{
				var oAudio = document.getElementsByTagName("audio")[0],
					oPlayControl = document.getElementById("play-control");

				document.getElementById("sound").addEventListener('click', function(event) {

					$(oPlayControl).toggleClass("playing").toggleClass("stopped");

					if (oAudio.paused)
					{
						oAudio.play();
						gtag('event', 'AudioPlay', {'event_category': 'Actions', 'event_label': oAudio.attributes.src.nodeValue});
					}
					else
					{
						oAudio.pause();
						oAudio.currentTime = 0;
						gtag('event', 'AudioStop', {'event_category': 'Actions', 'event_label': oAudio.attributes.src.nodeValue});
					}
				});

				oAudio.addEventListener('ended', function(event)
				{
					$(oPlayControl).removeClass("playing").addClass("stopped");
				});

				oAudio.addEventListener('error', function(event)
				{
					$(document.getElementById("sound")).remove();
				});

				//Auto-play (it is disabled in most of mobile browsers)
				oAudio.play();

				if (!oAudio.paused)
				{
					$(oPlayControl).removeClass("stopped").addClass("playing");
				}
			},

			stopSound: function()
			{
				var oAudio = document.getElementsByTagName("audio")[0],
					oPlayControl = document.getElementById("play-control");

				oAudio.pause();
				oAudio.currentTime = 0;
				$(oPlayControl).removeClass("playing").addClass("stopped");
			}
		};

		$(function() {
			NTSound.initializeSoundControl();
		});

		</script>
	@endif

	@yield('wizard_scripts')
@endsection
