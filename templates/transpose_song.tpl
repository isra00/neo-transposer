{% extends "base.tpl" %}

{% block meta_description %}{% trans with {'%song%': song_details.title} %}Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!{% endtrans %}{% endblock %}

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

<div class="transposition-feedback">
	<span class="question">{% trans %}Did this transposition work for you?{% endtrans %}</span>
	<span class="answers">
		<span class="answer"><button name="worked_1" class="flatbutton green" id="feedback-yes">Yes</button></span>
		<span class="answer"><button name="worked_0" class="flatbutton red" id="feedback-no">No</button></span>
	</span>
	<span class="thanks" id="feedback-thanks">Happy to know that! :-)</span>
	<ul id="reasons-no" class="hidden">
		<li>Quizá no has medido bien tu voz. <a href="{{ path('wizard_step1', {'_locale': app.locale}) }}">Haz click aquí para ir al asistente</a>.</li>
		<li>Quizá no lo has cantado de la misma forma que ha sido analizado para esta aplicación.</li>
		<li>Quizá no estás cantando en el mismo tono que la guitarra.</li>
	</ul>
</div>

{% import 'base.tpl' as self %}
{{ self.loadJsFramework() }}

<script>

NT = {
	showChart: function(oLinkContainer)
	{
		document.getElementById("voicechart-container").style.display = 'block';
		oLinkContainer.style.display = 'none';
	},

	feedbackYes: function()
	{
		$(".answers").hide();
		$("#feedback-thanks").show();
	},

	feedbackNo: function()
	{
		$(".question").add(".answer").hide();
		$("#reasons-no").show();
	}
};

$(function() {
	$("#feedback-yes").click(NT.feedbackYes)
	$("#feedback-no").click(NT.feedbackNo)
});
</script>

{% endblock %}
