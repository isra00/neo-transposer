 @extends('_base')

@section('content')

<form method="post" action="{{ route('chord_correction_panel') }}">
	@csrf

	@foreach ($songs as $song)
	<div style="overflow: hidden">
		<h2>{{ $song['page'] }} · {{ $song['title'] }}</h2>

		@if ($song['id_book'] == 1)
		<div style="float: right; width: 30rem; height: 200px; overflow: scroll">
			<img src="{{ $song['image'] }}" width="130%" />
		</div>
		@else
		<iframe style="float: right; width: 30rem" src="{{ $song['image'] }}" border="0">
		</iframe>
		@endif

		<ul>
		@foreach ($song['chords'] as $chord)
			<li>
				<input size="1" name="{{ $song['id_song'] }}_{{ $chord['chord'] }}" value="{{ $chord['position'] }}">
				{{ $chord['chord'] }}
			</li>
		@endforeach
		</ul>
	</div>
	@endforeach

	<button type="submit" name="sent" class="bigbutton">Update</button>

</form>

@endsection
