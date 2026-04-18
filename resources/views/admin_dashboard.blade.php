@extends('_base')
@use('App\Support\AdminViewHelpers')

@section('content')

<nav class="floating_toc" data-toc-levels="2">
	<ul></ul>
</nav>

<article class="row">
	<h2>Admin ops</h2>

	<nav class="admin-tools">
		<ul>
			<h4>Server Ops</h4>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'RefreshCompiledCss']) }}">Refresh compiled CSS</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'RemoveOldCompiledCss']) }}">Remove old CSS</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'PopulateUsersCountry']) }}">Populate Country data</a></li>
		</ul>

		<ul>
			<h4>DB maintenance</h4>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'CheckSongsRangeConsistency']) }}">Check lower-highest notes</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'CheckUsersRangeConsistency']) }}">Check user lower-highest notes</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'CheckChordsOrder']) }}">Check chord order</a></li>
			<li><a href="/admin/chord-correction">Chord correction panel</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'TestAllTranspositions']) }}">Functional test: all transpositions</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'CheckOrphanChords']) }}">Detect orphan chords</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'CheckMissingTranslations']) }}">Missing translations</a></li>
		</ul>

		<ul>
			<h4>Data research</h4>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'GetVoiceRangeOfGoodUsers']) }}">Get voice range of all good users</a></li>
			<li><a href="{{ route('admin_dashboard', ['tool' => 'GetPerformanceByNumberOfFeedbacks']) }}">Get Performance and frequency by # of FBs</a></li>
		</ul>

		<p><a href="{{ route('admin_dashboard', ['long' => 1]) }}" class="btn-neutral">Load long reports &#8690;</a></p>

	</nav>

</article>

<article class="db-status">
	<h2>Global status</h2>

	<div class="circles-container">
		<h4 class="data-circle">
			<span>Not null users</span>
			<b>{{ $users_reporting_fb['not_null_users'] }}</b>
			<small>Total: {{ $user_count }}</small>
		</h4>

		<h4 class="data-circle good-users">
			<span>Good users<br><small>voice range > 1oct</small></span>
			<b>{{ round(($good_users / $user_count) * 100) }}%</b>
			<small>{{ $good_users }}</small>
		</h4>
	</div>

	<h4>Song availability</h4>
	<div class="circles-container">
	@foreach ($song_availability as $book)
		<h4 class="data-circle small-circle">
			<span>{{ $book['lang_name'] }}</span>
			<b>{{ floor(($book['current'] / $book['total']) * 100) }}<small>%</small></b>
		</h4>
	@endforeach
	</div>

	<h4>People voice range</h4>
	<div class="circles-container">
	@foreach ($song_availability as $book)
		<h4 class="data-circle small-circle">
			<span>{{ $book['lang_name'] }}</span>
			<b>{{ floor(($book['peopledata'] / $book['current']) * 100) }}<small>%</small></b>
		</h4>
	@endforeach
	</div>

	<h4>Songbook #users and performance</h4>
	<table class="data-table">
		<thead>
			<tr><th>Language</th><th>Users</th><th>%</th><th colspan="2">Performance</th></tr>
		</thead>
		<tbody>
			@foreach ($usersByBook as $book)
				<tr>
					<td>{{ $book['lang_name'] }}</td>
					<td>{{ $book['users'] }}</td>
					<td>{{ round($book['percent'], 1) }}<small>%</small></td>
					<td>{!! AdminViewHelpers::feedbackGraph($performanceByBook[$book['id_book']]['yes'] ?? 0, $performanceByBook[$book['id_book']]['no'] ?? 0) !!}</td>
					@if (isset($performanceByBook[$book['id_book']]) && ($performanceByBook[$book['id_book']]['yes'] + $performanceByBook[$book['id_book']]['no']) > 0)
						<td>{{ round(($performanceByBook[$book['id_book']]['yes'] / ($performanceByBook[$book['id_book']]['yes'] + $performanceByBook[$book['id_book']]['no'])) * 100, 1) }}<small>%</small></td>
					@endif
				</tr>
			@endforeach
		</tbody>
	</table>

	<h4>Songs with URL</h4>
	<table class="data-table">
		<thead>
			<tr><th>Language</th><th colspan="2">Songs with URL</th></tr>
		</thead>
		<tbody>
			@foreach ($songsWithUrl as $book)
				<tr>
					<td>{{ $book['lang_name'] }}</td>
					<td>{!! AdminViewHelpers::feedbackGraph($book['with_url'], $book['total'] - $book['with_url']) !!}</td>
					<td>{{ round(($book['with_url'] / $book['total']) * 100) }}%</td>
				</tr>
			@endforeach
		</tbody>
	</table>

</article>

<article>

	<h2>Performance overview</h2>
	<table class="data-table table-with-graphs">
	@foreach ($global_performance as $group => $gp)
		<tr>
			<th>{{ $group }}:</th>
			<td>{{ round(($gp['yes'] / $gp['total']) * 100) }}<small>%</small> </td>
			<td>
				<div class="clearfix inline-graph">
					{!! AdminViewHelpers::feedbackGraph($gp['yes'], $gp['no']) !!}
				</div>
			</td>
		</tr>
	@endforeach
	</table>

	<h3>Users reporting feedback</h3>
	<table class="data-table table-with-graphs">
		<tr>
			@php $users_not_reporting_fb = $user_count - $users_reporting_fb['users_reporting_fb'] @endphp
			<th>Total:</th>
			<td>
				{{ round(($users_reporting_fb['users_reporting_fb'] / $user_count) * 100) }}<small>%</small>
			</td>
			<td>
				<div class="clearfix inline-graph">
					{!! AdminViewHelpers::feedbackGraph($users_reporting_fb['users_reporting_fb'], $users_not_reporting_fb) !!}
				</div>
			</td>
		</tr>
		<tr>
			@php $users_not_reporting_fb = $users_reporting_fb['not_null_users'] - $users_reporting_fb['users_reporting_fb'] @endphp
			<th>Not null:</th>
			<td>
				{{ round(($users_reporting_fb['users_reporting_fb'] / $users_reporting_fb['not_null_users']) * 100) }}<small>%</small>
			</td>
			<td>
				<div class="clearfix inline-graph">
					{!! AdminViewHelpers::feedbackGraph($users_reporting_fb['users_reporting_fb'], $users_not_reporting_fb) !!}
				</div>
			</td>
		</tr>
		<tr>
			@php $users_not_reporting_fb = $users_reporting_fb['good_users'] - $users_reporting_fb['users_reporting_fb'] @endphp
			<th>Good:</th>
			<td>
				{{ round(($users_reporting_fb['users_reporting_fb'] / $users_reporting_fb['good_users']) * 100) }}<small>%</small>
			</td>
			<td>
				<div class="clearfix inline-graph">
					{!! AdminViewHelpers::feedbackGraph($users_reporting_fb['users_reporting_fb'], $users_not_reporting_fb) !!}
				</div>
			</td>
		</tr>
	</table>

	<h3>Songs fb'ed by language</h3>

	<table class="data-table table-with-graphs">
	@foreach ($songs_with_fb as $book)
		@php $book_fb = $book['total'] - $book['nofb'] @endphp
		<tr>
			<th>{{ $book['lang_name'] }}:</th>
			<td>{{ round(($book_fb / $book['total']) * 100) }}<small>%</small></td>
			<td>
				<div class="clearfix inline-graph">{!! AdminViewHelpers::feedbackGraph($book_fb, $book['nofb']) !!}</div>
			</td>
		</tr>
	@endforeach
	</table>

	<h3>Performance by voice type</h3>
	<table class="data-table table-with-graphs">
		@foreach ($performanceByVoice as $voice)
			<tr>
				<th>{{ $voice['voiceType'] ?? '(unspecified)' }}</th>
				<td>{{ round($voice['performance'] * 100, 1) }}<small>%</small></td>
				<td>
					<div class="clearfix inline-graph">{!! AdminViewHelpers::feedbackGraph($voice['fbs'], $voice['fbs'] - round($voice['fbs'] * $voice['performance'])) !!}</div>
				</td>
			</tr>
		@endforeach
	</table>

</article>

<article>
	<h2>Performance by country</h2>

	<table class="data-table">
		<thead><tr>
			<th>Country</th>
			<th>Performance</th>
			<th>%</th>
			<th>good</th>
		</tr></thead>

		<tbody>
	@foreach ($perf_by_country as $perf)
			<tr>
				<td>
					<img src="{{ url('/static/img/flags/' . strtolower($perf['country']) . '.png') }}" width="16" />&nbsp;
					{{ $perf['country_name'] }}
				</td>
				<td>{!! AdminViewHelpers::feedbackGraph($perf['yes'], $perf['no']) !!}</td>
				<td>{{ round($perf['performance']) }}<small>%</small></td>
				<td>{{ round($perf['good_users'] * 100) }}<small>%</small></td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Good users day by day</h2>

	<table class="data-table">
		<thead><tr>
			<th>Registr. day</th>
			<th>Goods/bads</th>
			<th>%</th>
		</tr></thead>
		<tbody>
	@foreach ($good_users_chrono ?? [] as $day)
			<tr>
				<td>{{ $day['day'] }}</td>
				<td>{!! AdminViewHelpers::feedbackGraph($day['goods'], $day['total']) !!}</td>
				<td>{{ $day['goods_rate'] }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Global performance day by day</h2>

	<table class="data-table">
		<thead><tr>
			<th>Date</th>
			<th>Cumulative</th>
			<th>Perf</th>
			<th>Day</th>
			<th>Perf</th>
		</tr></thead>
		<tbody>
	@foreach ($global_perf_chrono ?? [] as $day)
			<tr>
				<td>{{ $day['day'] }}</td>
				<td>{!! AdminViewHelpers::feedbackGraph($day['c_yes'], $day['c_no']) !!}</td>
				<td>{{ round($day['c_performance']) }}<small>%</small></td>
				<td>{!! AdminViewHelpers::feedbackGraph($day['d_yes'], $day['d_no']) !!}</td>
				<td>{{ round($day['d_performance']) }}<small>%</small></td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Feedback ({{ count($feedback) }})</h2>

	<table class="data-table">
		<thead><tr>
			<th>Song</th>
			<th>Feedback</th>
			<th>Perf</th>
			<th>Wide</th>
		</tr></thead>

		<tbody>
	@foreach ($feedback as $song)
			<tr>
				<td>{{ $song['title'] }}</td>
				<td>{!! AdminViewHelpers::feedbackGraph($song['yes'], $song['no']) !!}</td>
				<td>{{ round($song['performance'], 2) }}</td>
				<td>{{ $song['wideness'] }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Unsuccessful songs</h2>

	<table class="data-table">
		<thead><tr>
			<th>Song</th>
			<th>Feedback</th>
			<th>Total</th>
			<th>Perf</th>
			<th>Wide</th>
		</tr></thead>

		<tbody>
	@foreach ($feedback as $song)
		@if ($song['performance'] < 0.5)
			<tr>
				<td>{{ $song['title'] }}</td>
				<td>{!! AdminViewHelpers::feedbackGraph($song['yes'], $song['no']) !!}</td>
				<td>{{ $song['yes'] + $song['no'] }}</td>
				<td>{{ round($song['performance'], 2) }}</td>
				<td>{{ $song['wideness'] }}</td>
			</tr>
		@endif
	@endforeach
		</tbody>
	</table>
</article>

<article class="row">
	<h2>Most active users ({{ count($most_active_users) }})</h2>

	<table class="data-table">
		<thead><tr>
			<th>ID</th>
			<th>E-mail</th>
			<th>Voice</th>
			<th>FB</th>
			<th>Perf</th>
			<th>Low/high attempts</th>
			<th>Country</th>
		</tr></thead>
		<tbody>
	@foreach ($most_active_users as $user)
			<tr>
				<td>{{ $user['id_user'] }}</td>
				<td>{{ $user['email'] }}</td>
				<td>{{ $user['lowest_note'] }} - {{ $user['highest_note'] }}</td>
				<td>{{ $user['total'] }}</td>
				<td>{!! AdminViewHelpers::feedbackGraph($user['yes'], $user['no']) !!}</td>
				<td>{{ $user['wizard_lowest_attempts'] }} / {{ $user['wizard_highest_attempts'] }}</td>
				<td><img src="https://cdn1.iconfinder.com/data/icons/famfamfam_flag_icons/{{ strtolower($user['country']) }}.png" width="16" />&nbsp;
					{{ $countries[$user['country']] ?? '' }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article class="half">
	<h2>Unhappy (fb < 0.5) users ({{ count($unhappy_users) }})</h2>

	<table class="data-table">
		<thead><tr>
			<th>ID</th>
			<th>Unhappy</th>
			<th>Action</th>
			<th>Old perf</th>
			<th>Current FB</th>
			<th>Total</th>
			<th>Current perf</th>
		</tr></thead>
		<tbody>
	@foreach ($unhappy_users as $user)
			<tr>
				<td>{{ $user['id_user'] }}</td>
				<td>{{ $user['time_unhappy'] ?? '-' }}</td>
				<td>{{ $user['action'] ?? '-' }}</td>
				<td>@if ($user['perf_before_action'] ?? null){{ round($user['perf_before_action'], 2) }}@endif</td>
				<td>{!! AdminViewHelpers::feedbackGraph($user['yes'], $user['no']) !!}</td>
				<td>{{ 0 + $user['total'] }}</td>
				<td class="@if ($user['perf_before_action'] ?? null){{ $user['perf'] > $user['perf_before_action'] ? 'green' : 'red' }}@endif">{{ round($user['perf'], 2) }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Detailed FB: transposition</h2>

	<table class="data-table">
		<thead><tr>
			<th>Transposition</th>
			<th>FBs</th>
			<th>%</th>
		</tr></thead>
		<tbody>
	@foreach ($dfb_transposition as $fbs)
			<tr>
				<td>{{ $fbs['transposition'] ?? '[unspecified]' }}</td>
				<td>{{ $fbs['fbs'] }}</td>
				<td>{{ round($fbs['fbs_relative'] * 100) }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Detailed FB: PC status</h2>

	<table class="data-table">
		<thead><tr>
			<th>PC Status</th>
			<th>FBs</th>
			<th>% PC</th>
		</tr></thead>
		<tbody>
	@foreach ($dfb_pc_status as $fbpc)
			<tr>
				<td>{{ $fbpc['pc_status'] ?? '[unspecified]' }}</td>
				<td>{{ $fbpc['fbss'] }}</td>
				<td>{{ round(($fbpc['chosePeopleCompatible'] / $fbpc['fbss']) * 100) }}<small>%</small></td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Detailed FB: centered score rate</h2>

	<table class="data-table">
		<thead><tr>
			<th>Song#</th>
			<th>Title</th>
			<th>Time</th>
			<th>Score rate</th>
		</tr></thead>
		<tbody>
	@foreach ($dfb_centered_scorerate as $fb)
			<tr>
				<td>{{ $fb['id_song'] }}</td>
				<td>{{ $fb['title'] }}</td>
				<td>{{ $fb['time'] }}</td>
				<td>{{ $fb['centered_score_rate'] }}</td>
			</tr>
	@endforeach
		</tbody>
	</table>
</article>

<article>
	<h2>Detailed FB: deviation from center</h2>

	<table class="data-table">
		<thead><tr>
			<th>Transposition</th>
			<th>Deviation</th>
			<th># fb</th>
		</tr></thead>
		<tbody>
		@foreach ($dfb_deviation as $row)
			<tr>
				<td>{{ $row['transposition'] ?? '-' }}</td>
				<td>{{ $row['deviation_from_center'] }}</td>
				<td>{{ $row['fbs'] }}</td>
			</tr>
		@endforeach
		</tbody>
	</table>
</article>

@section('scripts')
<script>
BC3 = {
    generateToc: function()
    {
        toc_level = $(".floating_toc").data("toc-levels");
        titulos = $("h" + toc_level);
        for (i in titulos.get())
        {
            marker = "h" + toc_level + "_" + i;

            (function(j, marker, titulos) {
                $(".floating_toc ul").append('<li><a href="#' + marker + '">' + titulos[i].innerHTML + '</a></li>');
            })(i, marker, titulos);

            $(titulos[i]).html(
            	'<a name="' + marker + '"></a>'
            	+ $(titulos[i]).html()
            	+ '<a href="#h2_0"> &uarr; </a>'
            );
        }

        $("body").addClass("with-floating-toc");
    },
};

$(function() {
    BC3.generateToc();
});
</script>
@endsection

@endsection
