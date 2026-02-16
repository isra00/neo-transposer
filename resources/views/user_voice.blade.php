@extends('_base')

@section('page_class', 'page-user-voice')

@section('content')

	<h1>@lang('Welcome to :software', ['software' => '<span class="software-name">' . config('nt.software_name') . '</span>'])</h1>

	<p>@lang('This software calculates the perfect transposition of each song for <em>your</em> voice. But first, it needs to know your voice range.')</p>

	<nav class="two-choices">
		<a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}" class="flatbutton red">@lang('<span>I don\'t know</span> my voice range')</a>
		<a href="javascript:void(0)" id="i-know" class="flatbutton red">@lang('<span>I do know</span> my voice range')</a>
	</nav>

	<form method="get" action="{{ route('set_user_data') }}" id="voice-range" class="hidden">
		<p class="voice-selector">
			<span class="field">
				@lang('Lowest:')
				<select name="lowest_note" id="lowest_note">
				@foreach ($acoustic_scale as $i => $note)
					<option value="{{ $note }}1"@if(session('user')->range->lowest == $note . '1') selected="selected"@endif>{{ $acoustic_scale_nice[$i] }}</option>
				@endforeach
				</select>
			</span>

			<span class="field">
				@lang('Highest:')
				<select name="highest_note" id="highest_note">
				@foreach ($acoustic_scale as $i => $note)
					<option value="{{ $note }}1"@if(session('user')->range->highest == $note . '1') selected="selected"@endif>{{ $acoustic_scale_nice[$i] }}</option>
				@endforeach

				@for ($octave = 2; $octave <= 3; $octave++)
					<optgroup label="+{{ $octave - 1 }} {{ trans_choice('{1}octave|[2,*]octaves', $octave - 1) }}">
					@foreach ($acoustic_scale as $i => $note)
						<option value="{{ $note }}{{ $octave }}"@if(session('user')->range->highest == $note . $octave) selected="selected"@endif>{{ $acoustic_scale_nice[$i] }} + {{ $octave - 1 }}{{ __('oct') }}</option>
					@endforeach
					</optgroup>
				@endfor
				</select>
			</span>
		</p>

		<input type="hidden" name="redirect" value="{{ $redirect }}">

		<p class="wizard-button"><a href="{{ route('wizard_step1', ['locale' => app()->getLocale()]) }}">@lang('If you don\'t know your highest and lowest note, click here')</a></p>

		<p class="center margintop">
			<button type="submit" value="sent" class="btn-neutral bigbutton">@lang('Continue')</button>
		</p>
	</form>

	<aside class="tip">
		<h3>@lang('What is the voice range?')</h3>
		<p>@lang('Everyone has a different voice: some people sing lower pitch, some higher. To know your voice range is to know exactly the limits of your voice: which lower and higher notes you are able to reach.')</p>
	</aside>

	<script>
	document.addEventListener('DOMContentLoaded', function() {

		document.getElementById("i-know").addEventListener("click", function(e) {
			this.parentNode.style.display = 'none';
			document.getElementById("voice-range").style.display = '';
			document.getElementById("voice-range").classList.remove('hidden');
			gtag('event', 'IKnowMyVoiceRange', {'event_category': 'Actions', 'event_label': '{{ session('user')->id_user }}'});
		});

		document.getElementById("voice-range").addEventListener("submit", function(e) {

			e.preventDefault();

			var notes			= ['C1','C#1','D1','D#1','E1','F#1','F1','F#1','G1','G#1','A1','A#1','B1'],
				lowest 			= document.getElementById('lowest_note').value,
				highest 		= document.getElementById('highest_note').value,
				index_highest	= notes.indexOf(highest);

			//Index < 0 means not found ==> above the 1st octave.
			if (index_highest > -1)
			{
				alert("@lang('Are you sure that is your real voice range? If you don\'t know, you can use the assistant to measure it.')");
				document.querySelector(".wizard-button").classList.add("blink");
				return false;
			}

			this.submit();
		});

	});
	</script>

@endsection
