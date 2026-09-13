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
