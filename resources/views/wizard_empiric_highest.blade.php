@extends('wizard_empiric_base')

@section('title')@lang('Step 3: let\'s find your highest note')@endsection

@section('instructions')
	{!! __('Now try to sing the following chorus in <strong class="blink">:song_key :song_capo</strong> with the following chords. Remember: you must sing it <strong>with high voice</strong>, but without doing falsetto! Make a little effort, we are almost done!', ['song_key' => $song_key, 'song_capo' => $song_capo]) !!}
@endsection

@section('answer_buttons')
	<button type="submit" name="can_sing" value="yes" class="big flatbutton green {{ $action_yes ?? '' }}" id="yes">@lang('Yes')</button>
	<button type="submit" name="can_sing" value="no" class="big flatbutton red {{ $action_no ?? '' }}" id="no">@lang('No, it\'s too high')</button>
@endsection

@section('messages')
	<div class="high-first-time hidden test-msg">
		<h3>@lang('Well, that is a bit strange')</h3>
		<div class="inside">
			<p>@lang('According to the voice you have defined, this tone should not be too high for you. Maybe you should go back to the first step and choose better your voice type... or maybe just repeat the test making sure you are singing in the same tone as the guitar.')</p>
			<nav>
				<a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}" class="big flatbutton red">&larr; @lang('Change my voice type')</a>
				&nbsp;
				<a href="javascript:void(0)" class="big flatbutton red">@lang('Repeat the test')</a>
			</nav>
		</div>
	</div>

	<div class="too-high hidden test-msg">
		<h3>@lang('That\'s not possible...')</h3>
		<div class="inside">
			<p>@lang('You have clicked "Yes" many times, but I don\'t think it\'s really possible that you can sing that high. Please verify that you are following properly the steps, or click on "Finish here" if you want to finish the test.')</p>
			<nav>
				<a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}" class="big flatbutton red">@lang('Repeat the test')</a>
				&nbsp;
				<button type="button" id="fsubmit" class="big flatbutton green">@lang('Finish here')</button>
			</nav>
		</div>
	</div>
@endsection

@section('wizard_scripts')
	<script>
	NT = {

		tooHigh: function()
		{
			$(".test-area").hide();
			$(".too-high").show();
			gtag('event', 'HighestTooHigh', {'event_category': 'WizardError', 'event_label': 'user_id: {{ session('user')->id_user }}'});

			$(document.getElementById("fsubmit")).click(function() {
				$(document.getElementById("yes").parentNode).append(
					'<input type="hidden" name="can_sing" value="no">'
				);
				document.forms[0].submit();
			});
		},

		preventFormSubmit: function()
		{
			$("#form-answer").submit(function(e) {
				e.preventDefault();
			});
		}
	};

	$(function()
	{
		$(".tooHigh").click(function(e) {
			NT.preventFormSubmit();
			NT.tooHigh();
		});
	});
	</script>
@endsection
