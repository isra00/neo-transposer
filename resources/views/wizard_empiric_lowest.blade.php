@extends('wizard_empiric_base')

@section('title')@lang('Step 2: let\'s find your lowest note')@endsection

@section('instructions')
	{!! __('Try to sing the following chorus in <strong class="blink">:song_key :song_capo</strong>, with the given chords. Remember: you must sing it with low voice, but without going too low, or you won\'t be able to sing loud enough in the community!', ['song_key' => $song_key, 'song_capo' => $song_capo]) !!}
@endsection

@section('answer_buttons')
	<button type="{{ $action_yes ? 'button' : 'submit' }}" name="can_sing" value="yes" class="big flatbutton green {{ $action_yes ?? '' }}" id="yes">@lang('Yes')</button>
	<button type="{{ $action_no ? 'button' : 'submit' }}" name="can_sing" value="no" class="big flatbutton red {{ $action_no ?? '' }}" id="no">@lang('No, it\'s too low')</button>
@endsection

@section('messages')
	<div class="low-first-time hidden test-msg">
		<h3>@lang('Well, that is a bit strange')</h3>
		<div class="inside">
			<p>@lang('According to the voice you have chosen in the previous step, that tone should not be too low. Maybe you should go back and choose better which type of voice is yours... or maybe simply repeat the test making sure that you are singing in the same tone as the guitar.')</p>
			<nav>
				<a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}" class="big flatbutton red">&larr; @lang('Change my voice type')</a>
				&nbsp;
				<a href="javascript:void(0)" class="big flatbutton red" id="repeat-test">@lang('Repeat the test')</a>
			</nav>
		</div>
	</div>

	<div class="too-low hidden test-msg">
		<h3>@lang('Well, that is a bit strange')</h3>
		<div class="inside">
			<p>@lang('If you have chosen properly your voice in the previous step and you have sung the previous attempts in the right tone with low voice, then it is practically impossible that you can sing lower than that. Maybe you should go back to the previous step and choose better your voice type:')</p>
			<nav><a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}" class="big flatbutton red">&larr; @lang('Change my voice type')</a></nav>

			<p>@lang('...or if you are sure that this super-low voice is yours, just go to the next step:')</p>
			<nav><a href="{{ route('wizard_empiric_highest', ['locale' => app()->getLocale()]) }}" class="big flatbutton green" id="repeat-test">@lang('Go to the next step') &rarr;</a></nav>
		</div>
	</div>
@endsection

@section('wizard_scripts')
	<script>
	NT = {

		lowFirstTime: function() {
			$(".test-area").hide();
			$(".low-first-time").show();
			NTSound.stopSound();
		},

		tooLow: function() {
			$(".test-area").hide();
			$(".too-low").show();
			gtag('event', 'LowestFirst', {'event_category': 'WizardError', 'event_label': 'user_id: {{ session('user')->id_user }}'});
			NTSound.stopSound();
		},

		repeatTest: function() {
			document.forms[0].submit();
		},

		preventFormSubmit: function() {
			$("#form-answer").submit(function(e) {
				e.preventDefault();
			});
		}
	};

	$(function() {
		$(".lowFirstTime").click(function(e) {
			NT.preventFormSubmit();
			NT.lowFirstTime();
		});

		$(".tooLow").click(function(e) {
			NT.preventFormSubmit();
			NT.tooLow();
		});

		$("#repeat-test").click(NT.repeatTest);
	});
	</script>
@endsection
