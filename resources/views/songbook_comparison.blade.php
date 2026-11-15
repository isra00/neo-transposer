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
		<span class="sc-legend-item sc-is-close">≤20% chords differ</span>
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
					<span class="sc-name">{{ $row['unique_song']['name'] }}</span>
					{{-- What differs is told by each column; the row only says how many books have it. --}}
					<span class="sc-row-flags">
						<span class="sc-count @if($row['books_missing']) sc-count-missing @endif">{{ $row['books_present'] }}/{{ count($selected_books) }}</span>
						@if('unlinked' === $row['status'])
						<span class="sc-flag sc-is-unlinked" title="Set this song's id_origin_song to compare it with the other books">no unique song</span>
						@endif
					</span>
				</th>

				@foreach($selected_books as $idBook)
				@php $cells = $row['cells'][$idBook] ?? []; @endphp
				<td class="sc-cell @empty($cells) sc-is-absent @endempty">
					@if($cells)
					<div class="sc-songs">
					@foreach($cells as $cell)
						@php
							$diff = $cell['diff'];
							$isReference = 'reference' === $diff['status'];
							//A lone song has nothing to be compared with: it shows its facts, no verdict.
							$compared = !$isReference && $row['books_present'] > 1;
							$ok = $compared && 'same' === $diff['status'];
							$identical = $ok && !$diff['key'];
						@endphp
						<div class="sc-song sc-is-{{ $diff['status'] }} @if($ok) sc-identical @endif @if($cell['verified_at']) sc-verified @endif">
						{{-- Floated first, so the title wraps around it instead of under it. --}}
						<label class="sc-verify" title="{{ $cell['verified_at'] ? 'Verified on ' . $cell['verified_at'] : 'Mark as verified' }}">
							<input type="checkbox" data-url="{{ route('songbook_comparison_verify', ['id_song' => $cell['id_song']]) }}" @checked($cell['verified_at'])>
							<svg class="sc-verify-icon" viewBox="0 0 16 16" aria-label="Mark as verified"><path d="M8 1l5.5 2v4.5c0 3.4-2.4 6.1-5.5 7.5-3.1-1.4-5.5-4.1-5.5-7.5V3z" /><path d="M5.3 8.2l1.9 1.9 3.6-3.8" /></svg>
						</label>
						<a class="sc-title" href="{{ route('edit_song', ['id_song' => $cell['id_song']]) }}">{{ $cell['title'] }}</a>@if('reference' === $cell['diff']['status'])<span class="sc-reference-star" title="This is the reference for this song">★</span>@endif
						<dl class="sc-rows">
							<dt>Chords</dt>
							<dd title="{{ implode(' ', $cell['chords']) }}">
								@if($compared)
									@if($diff['chords'])
									<span class="sc-verdict sc-is-chords">{{ $diff['chords_different'] }}/{{ $diff['chords_total'] }} {{ $diff['chords_reordered'] ? ' order' : '' }} different</span>
									@else
									<span class="sc-verdict sc-is-same">@unless($diff['key'])same @endunless✔︎</span>
									@endif
									@if($diff['key'])<span class="sc-verdict sc-is-key">different key</span>@endif
								@endif
								{{-- Identical to the reference in everything: its first chord adds nothing. --}}
								@unless($identical)
								<span class="sc-chip @if($diff['key']) sc-is-key {{ $magnitude($diff['key_delta']) }} @endif"
									@if($diff['key']) title="{{ abs($diff['key_delta']) }} semitones {{ $diff['key_delta'] > 0 ? 'above' : 'below' }} the reference" @endif>
									{{ $cell['key'] }}
									{{-- With the very same chords as the reference, an unknown tone does not matter. --}}
									@if(!$cell['key_is_tone'] && !($compared && !$diff['key'] && !$diff['chords']))
									<span title="First chord is not key"><svg class="sc-no-tone" viewBox="0 0 16 16" aria-label="First chord is not key"><circle cx="5" cy="8" r="3" /><path d="M8 8h6M12 8v2.5M14 8v2" /><path d="M2 14L14 2" /></svg></span>
									@endif
									@if($diff['key'])<b>{{ $diff['key_delta'] > 0 ? '↑' : '↓' }}{{ abs($diff['key_delta']) }}<abbr class="sc-st" title="semitones">st</abbr></b>@endif
								</span>
								@endunless
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
					@endforeach
					</div>
					@else
					<span class="sc-not-in-book">—</span>
					@endif
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

@section('scripts')
<script>
$(function() {
	$('.sc-verify input').on('change', function() {
		var checkbox = this,
			label = $(checkbox).parent();

		checkbox.disabled = true;
		$.ajax({
			type: 'POST',
			url: $(checkbox).data('url'),
			data: {verified: checkbox.checked ? 1 : 0},
			dataType: 'json',
			success: function(response) {
				label.closest('.sc-song').toggleClass('sc-verified', !!response.verified_at);
				label.attr('title', response.verified_at ? 'Verified on ' + response.verified_at : 'Mark as verified');
			},
			error: function() {
				//Not saved: the checkbox goes back to what the database says.
				checkbox.checked = !checkbox.checked;
			},
			complete: function() {
				checkbox.disabled = false;
			}
		});
	});
});
</script>
@endsection
