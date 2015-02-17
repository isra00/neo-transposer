{% extends "wizard_empiric_base.tpl" %}

{% block title %}{% trans %}Step 3: let's find your highest note{% endtrans %}{% endblock %}

{% block instructions %}
	{% trans with {'%song_key%': song_key, '%song_capo%': song_capo} %}Now try to sing the following chorus in <strong class="blink">%song_key% %song_capo%</strong> with the following chords. Remember: you must sing it <strong>with high voice</strong>, but without doing falsetto! Make a little effort, we are almost done!{% endtrans %}
{% endblock %}

{% block answer_buttons %}
	<p><button type="submit" name="can_sing" value="yes" class="big flatbutton green {{ action_yes|default() }}" id="yes">{% trans %}Yes{% endtrans %}</button></p>
	<p><button type="submit" name="can_sing" value="no" class="big flatbutton red {{ action_no|default() }}" id="no">{% trans %}No, that's too high{% endtrans %}</button></p>
{% endblock %}

{% block messages %}
	<div class="high-first-time hidden test-msg">
		<h3>{% trans %}Well, that is a bit strange{% endtrans %}</h3>
		<p>{% trans %}According to the voice you have defined, this tone should not be too high for you. Maybe you should go back to the first step and choose better your voice type... or maybe just repeat the test making sure you are singing in the same tone as the guitar.{% endtrans %}</p>
		<p class="center margintop bigline">
			<a href="{{ path('wizard_step1') }}" class="big flatbutton red">{% trans %}Change my voice type{% endtrans %}</a>
			&nbsp;
			<a href="javascript:void(0)" class="big flatbutton red" id="repeat-test">{% trans %}Repeat the test{% endtrans %}</a>
		</p>
	</div>
{% endblock %}

{% block js %}
	{# @todo Implementar el mensaje de arriba!!! #}
{% endblock %}