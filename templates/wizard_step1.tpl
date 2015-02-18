{% extends "base.tpl" %}

{% block page_class %}voice-wizard{% endblock %}

{% block content %}

	<h1>{% trans %}Voice measure wizard{% endtrans %}</h1>

	<p>{% trans %}If you can't measure your voice properly, follow these steps and the application will estimate your highest and lowest note{% endtrans %}</p>
	<p>{% trans %}<strong>Remember, it's only an estimate</strong>. The application can't hear your voice, so it is impossible to have full accuracy. Remember also that, if your voice is not measured precisely, the transpositions will not be optimal for your voice, most probably. In short, the Neo-Transposer will be completely useless for you.{% endtrans %}</p>

	<h2 class="step-1">{% trans %}Step 1{% endtrans %}</h2>

	<p>{% trans %}To start, choose one of these options:{% endtrans %}</p>

	<form method="post" action="{{ app.request.getRequestUri }}">

		<ul class="gender-selection">
			<li>
				<label for="male"><input type="radio" name="gender" value="male" id="male" onchange="NT.show('male')">
				{% trans %}I have male voice{% endtrans %}</label>

				<ul id="sub-male">
					<li>
						<label for="male_high"><input type="radio" name="gender" value="male_high" id="male_high">
						{% trans %}My voice is higher than most of men{% endtrans %}</label>
					</li>
					<li>
						<label for="male_low"><input type="radio" name="gender" value="male_low" id="male_low">
						{% trans %}My voice is lower than most of men{% endtrans %}</label>
					</li>
					<li>
						<label for="male_normal"><input type="radio" name="gender" value="male" id="male_normal">
						{% trans %}Neither higher nor lower / I don't know{% endtrans %}</label>
					</li>
				</ul>
			</li>

			<li>
				<label for="female"><input type="radio" name="gender" value="female" id="female" onchange="NT.show('female')">
				{% trans %}I have female voice{% endtrans %}</label>
				<ul id="sub-female">
					<li>
						<label for="female_high"><input type="radio" name="gender" value="female_high" id="female_high">
						{% trans %}My voice is higher than most of women{% endtrans %}</label>
					</li>
					<li>
						<label for="female_low"><input type="radio" name="gender" value="female_low" id="female_low">
						{% trans %}My voice is lower than most of women{% endtrans %}</label>
					</li>
					<li>
						<label for="female_normal"><input type="radio" name="gender" value="female" id="female_normal">
						{% trans %}Neither higher nor lower / I don't know{% endtrans %}</label>
					</li>
				</ul>
			</li>
		</ul>

		<p class="center margintop">
			<button type="submit" value="sent" class="bigbutton" id="submit">{% trans %}Next step &rarr;{% endtrans %}</button>
		</p>

	</form>
	
	{% import 'base.tpl' as self %}
	{{ self.loadJsFramework() }}
	
	<script>
	NT = {
		show: function(sGender)
		{
			document.getElementById('sub-' + sGender).style.display = 'block';
			sTheOther = (sGender == 'male' ? 'female' : 'male');
			document.getElementById('sub-' + sTheOther).style.display = 'none';
		}
	};

	$(function() {
	    $("#submit").attr('disabled', 'disabled');

	    $(".gender-selection ul input").change(function() {
	    	$("#submit").removeAttr('disabled');
	    });
	});
	</script>

{% endblock %}