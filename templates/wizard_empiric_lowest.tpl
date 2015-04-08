{% extends "wizard_empiric_base.tpl" %}

{% block title %}{% trans %}Step 2: let's find your lowest note{% endtrans %}{% endblock %}

{% block instructions %}
	{% trans with {'song_key': song_key, 'song_capo': song_capo} %}Try to sing the following chorus in <strong class="blink">%song_key% %song_capo%</strong>, with the given chords. Remember: you must sing it with low voice!{% endtrans %}
{% endblock %}

{% block answer_buttons %}
	<p><button type="submit" name="can_sing" value="yes" class="big flatbutton green {{ action_yes|default() }}" id="yes">{% trans %}Yes{% endtrans %}</button></p>
	<p><button type="submit" name="can_sing" value="no" class="big flatbutton red {{ action_no|default() }}" id="no">{% trans %}No, that's too low{% endtrans %}</button></p>
{% endblock %}

{% block messages %}
	<div class="low-first-time hidden test-msg">
		<h3>{% trans %}Well, that is a bit strange{% endtrans %}</h3>
		<p>{% trans %}According to the voice you have chosen in the previous step, that tone should not be too low. Maybe you should go back and choose better which type of voice is yours... or maybe simply repeat the test making sure that you are singing in the same tone as the guitar.{% endtrans %}</p>
		<p class="center margintop bigline">
			<a href="{{ path('wizard_step1') }}" class="big flatbutton red">{% trans %}Change my voice type{% endtrans %}</a>
			&nbsp;
			<a href="javascript:void(0)" class="big flatbutton red" id="repeat-test">{% trans %}Repeat the test{% endtrans %}</a>
		</p>
	</div>

	<div class="too-low hidden test-msg">
		<h3>{% trans %}Well, that is a bit strange{% endtrans %}</h3>
		<p>{% trans %}If you have chosen properly your voice in the previous step and you have sang the previous attempts in the right tone with low voice, then it is practically impossible that you can sing lower than that. Maybe you should go back to the previous step and choose better your voice type... or if you are sure that this super-low voice is yours, just go to the next step:{% endtrans %}</p>
		<p class="center margintop bigline">
			<a href="{{ path('wizard_step1') }}" class="big flatbutton red">{% trans %}Change my voice type{% endtrans %}</a>
			&nbsp;
			<a href="{{ path('wizard_empiric_highest') }}" class="big flatbutton green" id="repeat-test">{% trans %}Go to the next step{% endtrans %}</a>
		</p>
	</div>
{% endblock %}

{% block js %}
	<script>
	NT = {
		
		lowFirstTime: function()
		{
			$(".test-area").hide();
			$(".low-first-time").show();
		},

		tooLow: function()
		{
			$(".test-area").hide();
			$(".too-low").show();
			ga('send', 'event', 'WizardError', 'LowestFirst', 'user_id: {{ app.user.id_user }}');
		},

		repeatTest: function()
		{
			document.forms[0].submit();
		},

		preventFormSubmit: function()
		{
			$("#form-answer").submit(function(e) {
				e.preventDefault();
			});
		}
	};

	$(function()
	{
		$(".lowFirstTime").click(function(e) {
			NT.preventFormSubmit();
			NT.lowFirstTime();
		});

		$(".tooLow").click(function(e) {
			NT.preventFormSubmit();
			NT.tooLow();
		});

		$("#repeat-test").click(NT.repeatTest);
	});
	</script>
{% endblock %}