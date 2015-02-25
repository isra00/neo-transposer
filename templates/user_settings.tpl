{% extends "base.tpl" %}

{% block page_class %}user-settings{% endblock %}

{% block content %}

	<h1>{% trans with {'%software%': '<span class="software-name">' ~ app.neoconfig.software_name ~ '</span>'} %}Welcome to %software%{% endtrans %}</h1>

	<form method="get" action="{{ path('set_user_data') }}" onsubmit="return check(this)">
		<p>{% trans %}This software analyses the songs and your voice, giving you the perfect transposition for each song, according to your voice. But to do so, first I need to know your voice.{% endtrans %}</p>

		<h3>{% trans %}Which is the lowest note that you can sing? And the highest one?{% endtrans %}</h3>

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

		<a name="book"></a>
		<h3>{% trans %}Which songbook do you want to transpose?{% endtrans %}</h3>

		<select name="book">
		{% for id, book in app.books %}
			<option value="{{ id }}"{% if default_book_locale == book.locale %} selected="selected"{% endif %}>{{ book.lang_name }} ({{ book.details}})</option>
		{% endfor %}
		</select>

		<input type="hidden" name="redirect" value="{{ redirect }}">

		<p class="center margintop">
			<button type="submit" value="sent" class="bigbutton">{% trans %}We are ready!{% endtrans %}</button>
		</p>
	</form>

	<script>
	/**
	 * Check whether the selected highest note is higher that the lowest one.
	 * 
	 * @param  {object} form The form object.
	 * @return {boolean} Whether the form is valid or not.
	 */
	function check(form)
	{
		var notes			= ['C1','C#1','D1','D#1','E1','F#1','F1','F#1','G1','G#1','A1','A#1','B1'],
			lowest 			= document.getElementById('lowest_note').value,
			highest 		= document.getElementById('highest_note').value,
			index_highest	= notes.indexOf(highest);
		
		//Index < 0 means not found ==> above the 1st octave.
		if (notes.indexOf(lowest) >= index_highest && index_highest > -1)
		{
			alert('{% trans %}You have to select a highest note which is higher than the lowest one.{% endtrans %}');
			return false;
		}
		return true;
	}
	</script>
{% endblock %}