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
<script>

NotesCalculator = {

	aAccousticScale: ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'],
	aNumberedScale: [],

	init: function() {
		var i=0;

		for (i = 1; i < 5; i++) {
			for (sNote in this.aAccousticScale) {
				this.aNumberedScale.push(this.aAccousticScale[sNote] + String(i));
			}
		}
	},

	noteNumber: function(sNote) {
		var n = this.aNumberedScale.indexOf(sNote);
		return (n < 0) ? false : n;
	}
};

function forceKeyPressUppercase(e) {
	var charInput = e.keyCode;
	if((charInput >= 97) && (charInput <= 122)) {
	  if(!e.ctrlKey && !e.metaKey && !e.altKey) {
	    var newChar = charInput - 32;
	    var start = e.target.selectionStart;
	    var end = e.target.selectionEnd;
	    e.target.value = e.target.value.substring(0, start) + String.fromCharCode(newChar) + e.target.value.substring(end);
	    e.target.setSelectionRange(start+1, start+1);
	    e.preventDefault();
	  }
	}
}

$(function() {
	NotesCalculator.init();

	document.getElementById("lowest_note").addEventListener("keypress", forceKeyPressUppercase, false);
	document.getElementById("highest_note").addEventListener("keypress", forceKeyPressUppercase, false);
	document.getElementById("people_lowest_note").addEventListener("keypress", forceKeyPressUppercase, false);
	document.getElementById("people_highest_note").addEventListener("keypress", forceKeyPressUppercase, false);

	document.getElementsByTagName("form")[0].addEventListener('keyup', function(eTheForm) {
		var eLowest = document.getElementById("lowest_note"),
			eHighest = document.getElementById("highest_note"),
			ePeopleLowest = document.getElementById("people_lowest_note"),
			ePeopleHighest = document.getElementById("people_highest_note"),
			chordInputs;
			chords = []
			noDuplicateChords = true;

		if (eLowest.value.length && eHighest.value.length) {
			if (NotesCalculator.noteNumber(eLowest.value) >= NotesCalculator.noteNumber(eHighest.value)) {
				eLowest.setCustomValidity("Lowest is not lower than highest!");
			}
			else {
				eLowest.setCustomValidity("");
			}
		}

		if (ePeopleLowest.value.length && ePeopleHighest.value.length) {
			if (NotesCalculator.noteNumber(ePeopleLowest.value) >= NotesCalculator.noteNumber(ePeopleHighest.value)) {
				ePeopleLowest.setCustomValidity("Lowest is not lower than highest!");
			}
			else {
				ePeopleLowest.setCustomValidity("");
			}
		}

		chordInputs = document.getElementById("field-chords").querySelectorAll("input");

		(function (chords) {
			chordInputs.forEach(input => {
				if (!input.value.length) return;

				if (chords.indexOf(input.value) > -1) {
					noDuplicateChords = false;
					input.setCustomValidity("Duplicate chord");
				}
				else {
					chords.push(input.value);
					if (noDuplicateChords) {
						input.setCustomValidity("");
					}
				}
			});
		})(chords);
	});
});

</script>
@endsection
