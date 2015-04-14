{% extends "base.tpl" %}

{% block page_class %}page-user-voice{% endblock %}

{% block content %}

	<h1>{% trans with {'%software%': '<span class="software-name">' ~ app.neoconfig.software_name ~ '</span>'} %}Welcome to %software%{% endtrans %}</h1>

	<p>{% trans %}This software calculates the perfect transposition of each song for <em>your</em> voice. But first, it needs to know your voice range.{% endtrans %}</p>

	<nav class="two-choices">
		<a href="{{ path('wizard_step1') }}" class="flatbutton red">{% trans %}<span>I don't know</span> my voice range{% endtrans %}</a>
		<a href="javascript:void(0)" id="i-know" class="flatbutton red">{% trans %}<span>I do know</span> my voice range{% endtrans %}</a>
	</nav>

	<form method="get" action="{{ path('set_user_data') }}" id="voice-range" class="hidden">
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

		<input type="hidden" name="redirect" value="{{ redirect }}">

		<p class="wizard-button"><a href="{{ path('wizard_step1', {'_locale': app.locale}) }}">{% trans %}If you don't know your highest and lowest note, click here{% endtrans %}</a></p>

		<p class="center margintop">
			<button type="submit" value="sent" class="btn-neutral bigbutton">{% trans %}We are ready!{% endtrans %}</button>
		</p>
	</form>

	<aside class="tip">
		<h3>{% trans %}What is the voice range?{% endtrans %}</h3>
		<p>{% trans %}Everyone has a different voice: some people sing lower pitch, some higher. To know your voice range is to know exactly the limits of your voice: which lower and higher notes you are able to reach.{% endtrans %}</p>
	</aside>

	{% import 'base.tpl' as self %}
	{{ self.loadJsFramework() }}

	<script>
	$(function() {

		$(document.getElementById("i-know")).click(function(e) {
			$(this.parentNode).hide();
			$(document.getElementById("voice-range")).show();
		});

		$(document.getElementById("voice-range")).submit(function(e) {

			e.preventDefault();

			var notes			= ['C1','C#1','D1','D#1','E1','F#1','F1','F#1','G1','G#1','A1','A#1','B1'],
				lowest 			= document.getElementById('lowest_note').value,
				highest 		= document.getElementById('highest_note').value,
				index_highest	= notes.indexOf(highest);
			
			//Index < 0 means not found ==> above the 1st octave.
			if (index_highest > -1)
			{
				alert("{% trans %}Are you sure that is your real voice range? If you don't know, you can use the assistant to measure it.{% endtrans %}");
				$(".wizard-button").addClass("blink");
				return false;
			}

		    var form = this;
            form.submit();

		});

	});
	</script>

{% endblock %}