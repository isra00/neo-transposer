{% extends "base.tpl" %}

{% block page_class %}transpose-song{% endblock %}

{% macro printTransposition(transposition, original_chords) -%}
	<table class="transposition">
		<thead>
			<tr><th {% if not transposition.getAsBook %}colspan="3"{% endif %}>
				<strong>{{ transposition.chordsForPrint[0]|raw }} </strong>
				<span class="capo">{{ transposition.capoForPrint }}</span>
				{% if app.debug -%}
				<small class="score">[{{ transposition.score|round }}]</small>
				{%- endif %}
			</th></tr>
		</thead>
		<tbody>
		{% for chord in original_chords if not transposition.getAsBook -%}
			<tr>
				<td>{{ chord|raw }}</td>
				<td class="center">&rarr;</td>
				<td>{{ transposition.chordsForPrint[loop.index0]|raw }}</td>
			</tr>
		{%- else -%}
			<tr><td>{% trans %}(same chords as in the book){% endtrans %}</td></tr>
		{%- endfor %}
		</tbody>
	</table>
{%- endmacro %}
{% import _self as self %}

{% block content %}

{% if not app.user.isLoggedIn %}
	{% import "login.tpl" as login %}

	<div class="teaser">
		<div class="inside">
			<div class="more-inside">
				<p>{% trans with {'%song%': song_details.title } %}Neo-Transposer helps you to automatically transpose the chords of <strong>%song%</strong> so they match your voice. Type your e-mail, follow the steps and it will transpose all the songs of the Neocatechumenal Way for you!{% endtrans %}</p>
				{{ login.login_form('', app.request.getRequestUri) }}
			</div>
		</div>
	</div>
{% endif %}

{% if app.debug %}
<a href="/transpose/{{ next }}">NEXT &rarr;</a>
{% endif %}

<h1 class="song-title">
	<small class="page_number">{{ song_details.page }}</small>
	{{ song_details.title }}
</h1>

<div class="your-voice">
	<em>{% trans %}Your voice:{% endtrans %}</em> {{ your_voice|raw }}
	<a href="{{ path('user_settings', {'_locale': app.locale, 'redirect': app.request.getRequestUri}) }}" class="small-button">{% trans %}Change{% endtrans %}</a>
</div>

<h4>{% trans %}These two transpositions match your voice (they are equivalent). The first one has easier chords:{% endtrans %}</h4>
<div class="transpositions-list">
{% for transposition in transpositions %}
	{{ self.printTransposition(transposition, original_chords) }}
{% endfor %}
</div>

{% if not_equivalent %}
<h4>{% trans with {'%difference%': not_equivalent_difference} %}This other transposition is a bit %difference%, but it has easier chords and may also fit your voice:{% endtrans %}</h4>
<div class="transpositions-list">
	{{ self.printTransposition(not_equivalent, original_chords) }}
</div>
{% endif %}

<p class="show-voice-chart">
	<a href="javascript:void(0)" onclick="NT.showChart(this.parentNode)">{% trans %}Show voice chart{% endtrans %}</a>
</p>

<div id="voicechart-container">
	<table class="voicechart">
	{% for voice in voice_chart %}
		<tr class="{{ voice.css }}">
			<th>{{ voice.caption|trans }}</th>
			{% for i in range(0, voice.offset) %}<th>&nbsp;</th>{% endfor %}
			<td class="note">{{ voice.lowest }}</td>
			{% for i in range(0, voice.length) %}<td>██</td>{% endfor %}
			<td class="note">{{ voice.highest }}</td>
		</tr>
	{% endfor %}
	</table>
</div>

<a name="feedback"></a>
<form class="transposition-feedback" method="post" action="{{ path('transposition_feedback') }}">
	<input type="hidden" name="id_song" value="{{ song_details.id_song }}">
	<p class="question">{% trans %}Did this chords work for you?{% endtrans %}</p>
	<p class="answers bigline">
		<button type="submit" name="worked" value="1" class="flatbutton green" id="feedback-yes">{% trans %}Yes{% endtrans %}</button>
		<button type="submit" name="worked" value="0" class="flatbutton red" id="feedback-no">{% trans %}No{% endtrans %}</button>
	</p>
	<span class="thanks" id="feedback-thanks">{% trans %}Happy to know that! :-){% endtrans %}</span>
	<div class="social-buttons">
		<div class="fb-like" data-href="{{ app.absoluteUriWithoutQuery }}" data-width="90" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>
		<div class="g-plusone" data-annotation="none" data-align="right" data-href="{{ app.absoluteUriWithoutQuery }}"></div>
	</div>
	<ul id="reasons-no" class="hidden">
		{% if user_first_octave %}
		<li class="big">{% trans with {'%url%': url_wizard} %}It seems you have not measured your voice properly. Please, <a href="%url%">follow this instructions</a>.{% endtrans %}</li>
		{% else %}
		<li>{% trans with {'%url%': url_wizard} %}Maybe you didn't measure your voice properly. <a href="%url%">Click here to go to the Wizard</a>.{% endtrans %}</li>
		<li>{% trans %}Maybe you are not singing the song the same way it was analysed for the application{% endtrans %}</li>
		<li>{% trans %}Maybe you are not singing in the same tone as the guitar{% endtrans %}</li>
		{% endif %}
	</ul>
</form>

{% import 'base.tpl' as self %}
{{ self.loadJsFramework() }}

<script>

NT = {

	showChart: function(oLinkContainer)
	{
		document.getElementById("voicechart-container").style.display = 'block';
		oLinkContainer.style.display = 'none';
	},

	sendFeedback: function(iAnswer)
	{
		$.post(
			'{{ path('transposition_feedback') }}',
			{
				id_song: {{ song_details.id_song }},
				worked: iAnswer
			}
		);
	}
};

$(function() {

	$("#feedback-yes").click(function(e) {
		e.preventDefault();
		$(".answers").hide();
		$("#feedback-thanks").show();
		NT.sendFeedback(1);
		ga('send', 'event', 'FeedbackTransposition', 'Worked', '{{ song_details.title }}');
	});

	$("#feedback-no").click(function(e) {
		e.preventDefault();
		$(".question").add(".answers").add(".social-buttons").hide();
		$("#reasons-no").show();
		NT.sendFeedback(0);
		ga('send', 'event', 'FeedbackTransposition', 'NotWorked', '{{ song_details.title }}');
	});

});
</script>

{% endblock %}
