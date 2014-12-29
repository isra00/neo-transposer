{% extends "base.tpl" %}

{% block page_class %}transpose-song{% endblock %}

{% macro printTransposition(transposition, original_chords) %}
	<table class="transposition">
		<thead>
			<th colspan="3">
				<!--{{ transposition.score }}-->
				<strong>{{ transposition.chords[0]|raw }} </strong>
				{{ transposition.capo ? ' with capo ' ~ transposition.capo : ' (no capo)' }}
			</th>
		</thead>
		<tbody>
		{% if transposition.getAsBook %}
			<tr><td>(same chords as in the book)</td></tr>
		{% else %}
		{% for chord in original_chords %}
			<tr>
				<td>{{ chord|raw }}</td>
				<td class="center">&rarr;</td>
				<td>{{ transposition.chords[loop.index0]|raw }}</td>
			</tr>
		{% endfor %}
		{% endif %}
		</tbody>
	</table>
{% endmacro %}
{% import _self as self %}

{% block content %}
<h1 class="song-title">
	<small class="page_number">{{ song_details.page }}</small>
	{{ song_details.title }}
</h1>

<div class="your-voice">
	<em>Your voice:</em>
	{{ your_voice.from }} &rarr; {{ your_voice.to }} oct
	<a href="wizard.php" class="small-button">Change</a>
</div>

<h4>These two transpositions match your voice (they are equivalent):</h4>
<div class="transpositions-list ovhid">
{% for transposition in transpositions %}
	{{ self.printTransposition(transposition, original_chords) }}
{% endfor %}
</div>

{% if not_equivalents[0] %}
<h4>This other transposition is a bit {{ (not_equivalents[0].deviationFromPerfect > 0) ? 'higher' : 'lower' }}, but it has easier chords and may also fit your voice:</h4>
<div class="transpositions-list ovhid">
	{{ self.printTransposition(not_equivalents[0], original_chords) }}
</div>
{% endif %}

<div class="voicechart-container">
	<table class="voicechart">
	{% for voice in voice_chart %}
		<tr class="{{ voice.css }}">
			<th>{{ voice.caption }}</tb>
			{% for i in range(0, voice.offset) %}<th>&nbsp;</th>{% endfor %}
			<td class="note">{{ voice.lowest }}</td>
			{% for i in range(0, voice.length) %}<td>██</td>{% endfor %}
			<td class="note">{{ voice.highest }}</td>
		</tr>
	{% endfor %}
	</table>
</div>
{% endblock %}
