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
		<span class="sc-legend-item sc-is-unlinked">no unique song</span>
		<span class="sc-legend-note">
			{{ $stats['unique_songs'] }} songs · {{ $stats['shared_by_all'] }} in every book · {{ $stats['exclusive'] }} in one book only
			@if($stats['unlinked'])
			· <strong>{{ $stats['unlinked'] }} not linked</strong>
			@endif
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
						@if('unlinked' === $row['status'])
						<span class="sc-flag sc-is-unlinked" title="Set this song's id_unique_song to compare it with the other books">no unique song</span>
						@elseif('alone' === $row['status'])
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
						@php
							$diff = $cell['diff'];
							$isReference = 'reference' === $diff['status'];
							//A lone song has nothing to be compared with: it shows its facts, no verdict.
							$compared = !$isReference && $row['books_present'] > 1;
							$identical = $compared && 'same' === $diff['status'];
						@endphp
						<div class="sc-song sc-is-{{ $diff['status'] }} @if($identical) sc-identical @endif">
						<a class="sc-title" href="{{ route('edit_song', ['id_song' => $cell['id_song']]) }}">{{ $cell['title'] }}</a>@if('reference' === $cell['diff']['status'])<span class="sc-reference-star" title="This is the reference for this song">★</span>@endif
						<dl class="sc-rows">
							<dt>Chords</dt>
							<dd title="{{ implode(' ', $cell['chords']) }}">
								{{-- Identical to the reference in everything: its first chord adds nothing. --}}
								@unless($identical)
								<span class="sc-chip @if($diff['key']) sc-is-key {{ $magnitude($diff['key_delta']) }} @endif"
									@if($diff['key']) title="{{ $diff['key_delta'] }} semitones from the reference" @endif>
									{{ $cell['key'] }}
									{{-- With the very same chords as the reference, an unknown tone does not matter. --}}
									@if(!$cell['key_is_tone'] && !($compared && !$diff['key'] && !$diff['chords']))
									<span title="First chord is not key"><svg class="sc-no-tone" viewBox="0 0 16 16" aria-label="First chord is not key"><circle cx="5" cy="8" r="3" /><path d="M8 8h6M12 8v2.5M14 8v2" /><path d="M2 14L14 2" /></svg></span>
									@endif
									@if($diff['key'])<b>{{ sprintf('%+d', $diff['key_delta']) }}</b>@endif
								</span>
								@endunless
								@if($compared)
									@if($diff['chords'])
									<span class="sc-verdict sc-is-chords">{{ $diff['chords_different'] }}/{{ $diff['chords_total'] }} different</span>
									@else
									<span class="sc-verdict sc-is-same">same ✔︎</span>
									@endif
									@if($diff['key'])<span class="sc-verdict sc-is-key">different key</span>@endif
								@endif
							</dd>

								{{-- A range equal to the reference's is noise: only a discrepancy is spelled out. --}}
								<dt>Voice</dt>
								<dd>
									@if($compared && !$diff['extension'])
									<span class="sc-verdict sc-is-same">same ✔︎</span>
									@else
									<span class="sc-fact @if($diff['extension']) sc-is-range {{ $magnitude($diff['extension_delta']) }} @endif">
										{{ $cell['lowest'] }}–{{ $cell['highest'] }} · {{ $cell['extension'] }}<abbr title="semitones">st</abbr>
										@if($diff['extension_delta'])<b>{{ sprintf('%+d', $diff['extension_delta']) }}</b>@endif
									</span>
									@endif
								</dd>

								<dt>People</dt>
								<dd>
									@if(null === $cell['people_extension'])
									<span class="sc-not-in-book" title="Not recorded">—</span>
									@elseif($compared && $diff['people_compared'] && !$diff['people_extension'])
									<span class="sc-verdict sc-is-same">same ✔︎</span>
									@else
									<span class="sc-fact @if($diff['people_extension']) sc-is-range {{ $magnitude($diff['people_extension_delta']) }} @endif">
										{{ $cell['people_lowest'] }}–{{ $cell['people_highest'] }} · {{ $cell['people_extension'] }}<abbr title="semitones">st</abbr>
										@if($diff['people_extension_delta'])<b>{{ sprintf('%+d', $diff['people_extension_delta']) }}</b>@endif
									</span>
									@endif
								</dd>
						</dl>
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
