@extends('_base')

@section('content')

<h1>Insert song</h1>

<form class="insert-song" method="post" action="{{ request()->getRequestUri() }}">
	@csrf

	<p>
		<label for="book">Book:</label>
		<select name="id_book" id="book">
		@foreach ($all_books as $id => $book)
			<option value="{{ $id }}" @if ($id == ($id_book ?? null)) selected="selected" @endif>{{ $book->langName() }}</option>
		@endforeach
		</select>
	</p>

	<p class="field-wide">
		<label for="title">Title:</label>
		<input type="text" name="title" id="title" size="50" autofocus required>
	</p>

	<p class="field-wide">
		<label for="url">URL:</label>
		<input name="url" id="url" type="text" size="50" pattern="^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$">
	</p>

	<p>
		<label for="page">Page:</label>
		<input name="page" id="page" type="text" size="3" pattern="^[0-9]+$">
	</p>

	<p>
		<label for="lowest_note">Lowest note:</label>
		<input name="lowest_note" id="lowest_note" type="text" size="3" required pattern="^[ABCDEFG]#?[1-4]$">
	</p>

	<p>
		<label for="highest_note">Highest note:</label>
		<input name="highest_note" id="highest_note" type="text" size="3" required pattern="^[ABCDEFG]#?[1-4]$">
	</p>

	<p>
		<label for="people_lowest_note">People Lowest note:</label>
		<input name="people_lowest_note" id="people_lowest_note" type="text" size="3" required pattern="^[ABCDEFG]#?[1-4]$">
	</p>

	<p>
		<label for="people_highest_note">People Highest note:</label>
		<input name="people_highest_note" id="people_highest_note" type="text" size="3" required pattern="^[ABCDEFG]#?[1-4]$">
	</p>

	<p class="field-chords" id="field-chords">
		<label>Chords:</label>
		@for ($i = 0; $i <= 9; $i++)
		<input name="chords[{{ $i }}]" type="text" size="2" pattern="^([ABCDEFG]#?b?)([mM45679]*|7?dim)$">
		@endfor
	</p>

	<p>
		<input type="checkbox" name="first_chord_is_key" checked="checked" id="first_chord_is_key">
		<label for="first_chord_is_key">First chord = key</label>
	</p>

	<p><button type="submit" id="submit" name="sent" class="bigbutton">Insert</button></p>

</form>

@endsection

@section('scripts')
@include('partial_song_form_scripts')
@endsection
