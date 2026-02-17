@extends('_base')

@section('content')

	<h1>@lang('Step 2: time to sing!')</h1>

	<p>@lang('Now I will propose you to sing the chorus of a song in a certain key. Take your guitar and try to sing it in that key.')</p>

	<p>@lang('If it is too low for you, click on “No, it\'s too low”. If you were able to sing it, click on “Yes”. Then, I will transpose the song so it will be a bit lower, and you should try again. We will keep repeating the exercise until you can\'t sing lower')</p>

	<p>@lang('Try to do your best to sing with your lowest possible voice, since this test tries to find the limits of your voice.')</p>

	<form method="post" action="{{ $form_action }}" class="margintop">
		<p class="center">
			<button id="submit" type="submit" value="sent" class="btn-neutral bigbutton">@lang('Understood')</button>
			<small id="countdown"><span id="seconds">5</span> @lang('seconds')...</small>
		</p>
	</form>

@endsection

@section('scripts')
	@if(!config('app.debug'))
	<script>
	$(function() {

		/** @see https://github.com/isra00/neo-transposer/issues/158 */
		setTimeout(function() {

			var oCountDown = document.getElementById("seconds");

			$("#submit").attr("disabled", "disabled");

			window.intervalButtonOk = setInterval(function () {
				var current = parseInt(oCountDown.innerHTML);
				current--;
				oCountDown.innerHTML = current.toString();

				if (current < 1)
				{
					$(document.getElementById("submit")).removeAttr("disabled");
					$(document.getElementById("countdown")).css("visibility", "hidden");
					clearInterval(intervalButtonOk);
				}
			}, 1000);
		}, 500);
	});
	</script>
	@endif
@endsection
