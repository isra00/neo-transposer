{% extends "base.tpl" %}

{% block page_class %}user-settings{% endblock %}

{% block content %}

	<h1>Welcome to {{ neoglobals.software_name }}</h1>

	<form method="get" action="{{ path('set_user_data') }}">
		<p>This software analyses the songs and your voice, giving you the perfect
		transposition for each song, according to your voice. But to do so, first
		I need to know your voice.</p>

		<h3>1. Which is the lowest note that you can sing? And the highest one?</h3>

		<p class="voice-selector">
			<span class="field">
				Lowest:
				<select name="lowest_note">
				{% for note in accoustic_scale %}
					<option value="{{ note }}1" {% if neoglobals.user.lowest_note == note ~ 1 %}selected="selected"{% endif %}>{{ note }}</option>
				{% endfor %}
				</select>
			</span>

			<span class="field">
				Highest:
				<select name="highest_note">
{% for note in accoustic_scale %}
					<option value="{{ note }}1" {% if neoglobals.user.highest_note == note ~ '1' %}selected="selected"'{% endif %}>{{ note }}</option>
{% endfor %}

{% for i in 2..3 %}
					<optgroup label="+{{ i - 1 }} octave{{ (i > 1) ? 's' }}">
	{% for note in accoustic_scale %}
						<option value="{{ note }}{{ i }}"{% if neoglobals.user.highest_note == note ~ i %} selected="selected"{% endif %}>{{ note }}</option>
	{% endfor %}
					</optgroup>
{% endfor %}
				</select>
			</span>
		</p>

		<a name="book"></a>
		<h3>Which songbook do you want to transpose?</h3>

		<select name="book">
		{% for id, book in neoglobals.books %}
			<option value="{{ id }}"{% if neoglobals.user.id_book == book.id_book %} selected="selected"{% endif %}>{{ book.lang_name }} ({{ book.details}})</option>
		{% endfor %}
		</select>

		<p class="center margintop">
			<button type="submit" value="sent" class="bigbutton">We are ready!</button>
		</p>
	</form>
{% endblock %}