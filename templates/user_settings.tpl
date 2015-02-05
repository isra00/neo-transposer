{% extends "base.tpl" %}

{% block page_class %}user-settings{% endblock %}

{% block content %}

	<h1>{% trans with {'%software%': '<span class="software-name">' ~ app.neoconfig.software_name ~ '</span>'} %}Welcome to %software%{% endtrans %}</h1>

	<form method="get" action="{{ path('set_user_data') }}">
		<p>{% trans %}This software analyses the songs and your voice, giving you the perfect transposition for each song, according to your voice. But to do so, first I need to know your voice.{% endtrans %}</p>

		<h3>{% trans %}Which is the lowest note that you can sing? And the highest one?{% endtrans %}</h3>

		<p class="voice-selector">
			<span class="field">
				{% trans %}Lowest:{% endtrans %}
				<select name="lowest_note">
				{% for note in accoustic_scale %}
					<option value="{{ note }}1"{% if app.user.lowest_note == note ~ 1 %} selected="selected"{% endif %}>{{ note|notation(current_notation) }}</option>
				{% endfor %}
				</select>
			</span>

			<span class="field">
				{% trans %}Highest:{% endtrans %}
				<select name="highest_note">
{% for note in accoustic_scale %}
					<option value="{{ note }}1"{% if app.user.highest_note == note ~ '1' %} selected="selected"'{% endif %}>{{ note|notation(current_notation) }}</option>
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

		<a name="book"></a>
		<h3>{% trans %}Which songbook do you want to transpose?{% endtrans %}</h3>

		<select name="book">
		{% for id, book in app.books %}
		{# FIXME: el default book es el del locale solo la primera vez, después debe ser el actual #}
			<option value="{{ id }}"{% if default_book == book.locale %} selected="selected"{% endif %}>{{ book.lang_name }} ({{ book.details}})</option>
		{% endfor %}
		</select>

		<p class="center margintop">
			<button type="submit" value="sent" class="bigbutton">{% trans %}We are ready!{% endtrans %}</button>
		</p>
	</form>
{% endblock %}