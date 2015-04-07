{% extends "base.tpl" %}

{% block page_class %}user-settings{% endblock %}

{% block content %}

	<h1>{% trans with {'%software%': '<span class="software-name">' ~ app.neoconfig.software_name ~ '</span>'} %}Welcome to %software%{% endtrans %}</h1>

	<p>{% trans %}This software analyses the songs and your voice, giving you the perfect transposition for each song, according to your voice. But to do so, first I need to know your voice.{% endtrans %}</p>

	<nav class="two-choices">
		<a href="{{ path('wizard_step1') }}" class="flatbutton red">{% trans %}<span>I don't know</span> my voice range{% endtrans %}</a>
		<a href="javascript:void(0)" id="i-know" class="flatbutton red">{% trans %}<span>I do know</span> my voice range{% endtrans %}</a>
	</nav>

	<section class="hidden" id="voice-range">
		<p class="voice-selector">
			<span class="field">
				{% trans %}Lowest:{% endtrans %}
				<select name="lowest_note" id="lowest_note">
				{% for note in accoustic_scale %}
					<option value="{{ note }}1"{% if app.user.lowest_note == note ~ 1 %} selected="selected"{% endif %}>{{ note|notation(current_notation) }}</option>
				{% endfor %}
				</select>
			</span>

			<span class="field">
				{% trans %}Highest:{% endtrans %}
				<select name="highest_note" id="highest_note">
{% for note in accoustic_scale %}
					<option value="{{ note }}1"{% if app.user.highest_note == note ~ '1' %} selected="selected"{% endif %}>{{ note|notation(current_notation) }}</option>
{% endfor %}

{% for i in 2..3 %}
					<optgroup label="+{{ i - 1 }} {% transchoice i - 1 %}{1}octave|]1,Inf]octaves{% endtranschoice %}">
{% for note in accoustic_scale %}
						<option value="{{ note }}{{ i }}"{% if app.user.highest_note == note ~ i %} selected="selected"{% endif %}>{{ note|notation(current_notation) }} + {{ i -1 }}{{ 'oct'|trans }}</option>
{% endfor %}
					</optgroup>
{% endfor %}
				</select>
			</span>
		</p>

		<p class="wizard-button"><a href="{{ path('wizard_step1', {'_locale': app.locale}) }}">{% trans %}If you don't know your highest and lowest note, click here{% endtrans %}</a></p>
	</section>

	{% import 'base.tpl' as self %}
	{{ self.loadJsFramework() }}

	<script>
	$(function() {
		$(document.getElementById("i-know")).click(function(e) {
			$(this.parentNode).hide();
			$(document.getElementById("voice-range")).show();
		});
	});
	</script>

{% endblock %}