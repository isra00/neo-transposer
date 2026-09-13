@extends('_base')

@section('content')
<article class="songbook-comparison">

	<h2>Songbook comparison</h2>

	<form method="get" action="{{ route('songbook_comparison') }}" class="sc-controls">

		<fieldset class="sc-books">
			<legend>Books</legend>
			@foreach($all_books as $book)
			<label class="sc-book-toggle">
				<input type="checkbox" name="books[]" value="{{ $book->idBook() }}"
					@checked(in_array($book->idBook(), $selected_books))>
				<span>{{ $book->langName() }}</span>
			</label>
			@endforeach
		</fieldset>

		<fieldset>
			<legend>Compare against</legend>
			<select name="reference">
				<option value="origin" @selected('origin' == $reference)>Each song's origin book</option>
				@foreach($all_books as $book)
				<option value="{{ $book->idBook() }}" @selected((string) $book->idBook() === (string) $reference)>{{ $book->langName() }}</option>
				@endforeach
			</select>
		</fieldset>

		<fieldset>
			<legend>Show</legend>
			<select name="filter">
				@foreach($filters as $value => $label)
				<option value="{{ $value }}" @selected($value == $filter)>{{ $label }}</option>
				@endforeach
			</select>
		</fieldset>

		<fieldset>
			<legend>Search</legend>
			<input type="search" name="search" value="{{ $search }}" placeholder="Title in any language">
		</fieldset>

		<button type="submit" class="btn">Apply</button>
	</form>

	<p class="sc-legend">
		<span class="sc-legend-item sc-is-same">identical</span>
		<span class="sc-legend-item sc-is-range">range differs</span>
		<span class="sc-legend-item sc-is-key">key differs</span>
		<span class="sc-legend-item sc-is-chords">chords differ</span>
		<span class="sc-legend-item sc-is-alone">only one book</span>
		<span class="sc-legend-item sc-is-absent">not in the book</span>
		<span class="sc-legend-note">
			{{ $stats['unique_songs'] }} songs · {{ $stats['shared_by_all'] }} in every book · {{ $stats['exclusive'] }} in one book only
		</span>
	</p>

	@php
		//How far apart, not just whether: the chips deepen with the size of the gap.
		$magnitude = fn(int $delta) => abs($delta) >= 6 ? 'sc-mag-high' : (abs($delta) >= 3 ? 'sc-mag-mid' : 'sc-mag-low');
	@endphp

	<table class="sc-table">
		<thead>
			<tr>
				<th class="sc-col-song">Unique song</th>
				@foreach($selected_books as $idBook)
				<th>
					{{ $all_books[$idBook]->langName() }}
					<small>{{ $stats['per_book'][$idBook] }} songs</small>
				</th>
				@endforeach
			</tr>
		</thead>
		<tbody>
		@foreach($rows as $row)
			<tr class="sc-row sc-is-{{ $row['status'] }}">
				<th class="sc-col-song" scope="row">
					{{-- The mapping note is context, not a difference: available on hover, out of the way. --}}
					<span class="sc-name @if($row['unique_song']['notes']) sc-has-note @endif"
						@if($row['unique_song']['notes']) title="{{ $row['unique_song']['notes'] }}" @endif>{{ $row['unique_song']['name'] }}</span>
					<span class="sc-row-flags">
						<span class="sc-count @if($row['books_missing']) sc-count-missing @endif">{{ $row['books_present'] }}/{{ count($selected_books) }}</span>
						@if('alone' === $row['status'])
						<span class="sc-flag sc-is-alone">only this book</span>
						@elseif('same' === $row['status'])
						<span class="sc-flag sc-is-same">identical</span>
						@else
						@if($row['differs']['chords'])<span class="sc-flag sc-is-chords">chords</span>@endif
						@if($row['differs']['key'])<span class="sc-flag sc-is-key">key</span>@endif
						@if($row['differs']['extension'] || $row['differs']['people_extension'])<span class="sc-flag sc-is-range">range</span>@endif
						@endif
					</span>
				</th>

				@foreach($selected_books as $idBook)
				@php $cells = $row['cells'][$idBook] ?? []; @endphp
				<td class="sc-cell @empty($cells) sc-is-absent @endempty">
					@forelse($cells as $cell)
					<div class="sc-song sc-is-{{ $cell['diff']['status'] }}">
						<a class="sc-title" href="{{ route('edit_song', ['id_song' => $cell['id_song']]) }}">{{ $cell['title'] }}</a>

						@if('reference' === $cell['diff']['status'])
						{{-- The baseline every other cell is expressed against. --}}
						<span class="sc-facts">
							<span class="sc-chip">{{ $cell['key'] }}@unless($cell['key_is_tone'])<i>?</i>@endunless</span>
							<span class="sc-fact">{{ $cell['lowest'] }}–{{ $cell['highest'] }} · {{ $cell['extension'] }}<abbr title="semitones">st</abbr></span>
						</span>
						@elseif('same' === $cell['diff']['status'])
						<span class="sc-same-mark" title="Same key, same chords and same range as the reference">identical</span>
						@else
						{{-- Only what actually differs, as a delta from the reference. --}}
						<span class="sc-facts">
							@if($cell['diff']['key'])
							<span class="sc-chip sc-is-key {{ $magnitude($cell['diff']['key_delta']) }}" title="Key {{ $cell['key'] }}, {{ $cell['diff']['key_delta'] }} semitones from the reference">
								{{ $cell['key'] }}@unless($cell['key_is_tone'])<i>?</i>@endunless
								<b>{{ sprintf('%+d', $cell['diff']['key_delta']) }}</b>
							</span>
							@endif

							@if($cell['diff']['extension'])
							<span class="sc-fact sc-is-range {{ $magnitude($cell['diff']['extension_delta']) }}" title="Voice range {{ $cell['lowest'] }}–{{ $cell['highest'] }}, {{ $cell['extension'] }} semitones">
								{{ $cell['extension'] }}<abbr title="semitones">st</abbr>
								<b>{{ sprintf('%+d', $cell['diff']['extension_delta']) }}</b>
							</span>
							@endif

							@if($cell['diff']['people_extension'])
							<span class="sc-fact sc-is-range sc-fact-people {{ $magnitude($cell['diff']['people_extension_delta']) }}" title="People range {{ $cell['people_lowest'] }}–{{ $cell['people_highest'] }}">
								people {{ $cell['people_extension'] }}<abbr title="semitones">st</abbr>
								<b>{{ sprintf('%+d', $cell['diff']['people_extension_delta']) }}</b>
							</span>
							@endif

							@if($cell['diff']['chords'])
							<span class="sc-degrees" title="Chords: {{ implode(' ', $cell['chords']) }}">
								@for($degree = 0; $degree < 12; $degree++)
								<i class="@if(in_array($degree, $cell['relative_chords'])) on @endif"></i>
								@endfor
							</span>
							@endif
						</span>
						@endif
					</div>
					@empty
					<span class="sc-not-in-book">—</span>
					@endforelse
				</td>
				@endforeach
			</tr>
		@endforeach
		</tbody>
	</table>

	@if([] === $rows)
	<p class="sc-empty">No unique song matches these filters.</p>
	@endif

</article>
@endsection
