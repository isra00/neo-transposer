@extends('_base')

@section('content')

<h1>Edit song</h1>

{{--
	Two separate forms on purpose: picking a book must not carry the song being edited,
	or the controller would resolve that song again and take the book from it, leaving
	the picker stuck on the current book. Each select submits only its own field.
--}}
<div class="song-picker">

	<form method="get" action="{{ route('edit_song') }}">
		<label for="picker_book">Book:</label>
		<select name="id_book" id="picker_book" onchange="this.form.submit()">
		@foreach ($all_books as $id => $book)
			<option value="{{ $id }}" @selected($id == $id_book)>{{ $book->langName() }}</option>
		@endforeach
		</select>
		<button type="submit" class="flatbutton">Go</button>
	</form>

	<form method="get" action="{{ route('edit_song') }}">
		<label for="picker_song">Song:</label>
		<select name="id_song" id="picker_song" onchange="window.location = '{{ route('edit_song') }}/' + this.value">
			<option value="">— pick a song ({{ count($book_songs) }}) —</option>
		@foreach ($book_songs as $bookSong)
			@php ($bookSong = (array) $bookSong)
			<option value="{{ $bookSong['id_song'] }}" @selected($song && $bookSong['id_song'] == $song->idSong)>
				{{ $bookSong['page'] }}. {{ $bookSong['title'] }}
			</option>
		@endforeach
		</select>
		<button type="submit" class="flatbutton">Go</button>
	</form>

	@if ($song)
	<p class="song-nav">
		@if ($siblings['prev'])
			<a href="{{ route('edit_song', ['id_song' => $siblings['prev']['id_song']]) }}" class="btn-neutral" accesskey="p">&larr; {{ $siblings['prev']['title'] }}</a>
		@endif
		@if ($siblings['next'])
			<a href="{{ route('edit_song', ['id_song' => $siblings['next']['id_song']]) }}" class="btn-neutral" accesskey="n">{{ $siblings['next']['title'] }} &rarr;</a>
		@endif
	</p>
	@endif

</div>

@if (!$song)
	<p class="no-song">Pick a song above to edit it.</p>
@else

<div class="edit-song-layout">

	<form class="insert-song edit-song" method="post" action="{{ route('edit_song', ['id_song' => $song->idSong]) }}">
		@csrf

		<p class="song-id">
			Song <strong>#{{ $song->idSong }}</strong> ·
			<a href="{{ route('transpose_song', ['id_song' => $song->slug]) }}" target="_blank">see it live</a>
		</p>

		<p>
			<label for="book">Book:</label>
			<select name="id_book" id="book">
			@foreach ($all_books as $id => $book)
				<option value="{{ $id }}" @selected($id == $song->idBook)>{{ $book->langName() }}</option>
			@endforeach
			</select>
		</p>

		<p class="field-wide">
			<label for="title">Title:</label>
			<input type="text" name="title" id="title" size="50" value="{{ $song->title }}" required>
		</p>

		<p class="field-wide">
			<label for="slug">Slug:</label>
			<input type="text" name="slug" id="slug" size="50" value="{{ $song->slug }}" required pattern="^[a-z0-9ñ\-]+$">
			<small>This is the song's public URL. Changing it breaks existing links.</small>
		</p>

		<p class="field-wide">
			<label for="url">URL:</label>
			<input name="url" id="url" type="text" size="50" value="{{ $song->url }}" pattern="^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$">
			<small>Songbook page image and audio shown here are read from this page.</small>
		</p>

		<p>
			<label for="page">Page:</label>
			<input name="page" id="page" type="text" size="3" value="{{ $song->page }}" pattern="^[0-9]+$">
		</p>

		<p>
			<label for="lowest_note">Lowest note:</label>
			<input name="lowest_note" id="lowest_note" type="text" size="3" value="{{ $song->range->lowest() }}" required pattern="^[ABCDEFG]#?[1-4]$">
		</p>

		<p>
			<label for="highest_note">Highest note:</label>
			<input name="highest_note" id="highest_note" type="text" size="3" value="{{ $song->range->highest() }}" required pattern="^[ABCDEFG]#?[1-4]$">
		</p>

		<p>
			<label for="people_lowest_note">People Lowest note:</label>
			<input name="people_lowest_note" id="people_lowest_note" type="text" size="3" value="{{ $song->peopleRange?->lowest() }}" pattern="^[ABCDEFG]#?[1-4]$">
		</p>

		<p>
			<label for="people_highest_note">People Highest note:</label>
			<input name="people_highest_note" id="people_highest_note" type="text" size="3" value="{{ $song->peopleRange?->highest() }}" pattern="^[ABCDEFG]#?[1-4]$">
		</p>

		<p class="field-chords" id="field-chords">
			<label>Chords:</label>
			@for ($i = 0; $i < max(10, count($song->originalChords)); $i++)
			<input name="chords[{{ $i }}]" type="text" size="2" value="{{ $song->originalChords[$i] ?? '' }}" pattern="^([ABCDEFG]#?b?)([mM45679]*|7?dim)$">
			@endfor
			<small>In order: the first one is the song's key when the box below is ticked.</small>
		</p>

		<p>
			<input type="checkbox" name="first_chord_is_key" id="first_chord_is_key" @checked($song->firstChordIsTone)>
			<label for="first_chord_is_key">First chord = key</label>
		</p>

		<p>
			<label for="artistic_adjustment">Artistic adjustment:</label>
			<input name="artistic_adjustment" id="artistic_adjustment" type="text" size="3" value="{{ $song->artisticAdjustment }}" pattern="^-?[0-9]+$">
		</p>

		<p class="submit-row">
			<button type="submit" id="submit" name="sent" class="bigbutton">Save</button>
			@if ($siblings['next'])
			<button type="submit" name="go_to_next" value="1" class="bigbutton btn-neutral">Save &amp; next &rarr;</button>
			@endif
		</p>

	</form>

	<aside class="song-media">
		@if (!$song->url)
			<p class="notification error">This song has no URL, so there is no songbook page image or audio to show.</p>
		@elseif ($media->error)
			<p class="notification error">Could not read <a href="{{ $song->url }}" target="_blank">the song page</a>: {{ $media->error }}</p>
		@else
			<h3>
				<a href="{{ $song->url }}" target="_blank">{{ parse_url($song->url, PHP_URL_HOST) }} &#8599;</a>
			</h3>

			@if ($media->audioUrl)
				<audio controls preload="none" src="{{ $media->audioUrl }}"></audio>
			@else
				<p class="notification error">No audio found on the song page.</p>
			@endif

			@if ($media->pdfUrl)
				<iframe class="songbook-page-image" src="{{ $media->pdfUrl }}" title="Songbook page image of {{ $song->title }}"></iframe>
				<p><a href="{{ $media->pdfUrl }}" target="_blank">Open the songbook page image in a new tab &#8599;</a></p>
			@elseif ($media->imageUrls)
				{{-- Publishers without a PDF publish one songbook page image per page. --}}
				<div class="songbook-page-image songbook-page-images">
					@foreach ($media->imageUrls as $i => $imageUrl)
						<img src="{{ $imageUrl }}" alt="Songbook page image of {{ $song->title }}, page {{ $i + 1 }}" loading="lazy">
					@endforeach
				</div>
			@else
				<p class="notification error">No songbook page image found on the song page.</p>
			@endif
		@endif
	</aside>

</div>

@endif

@endsection

@section('scripts')
@include('partial_song_form_scripts')
@endsection
