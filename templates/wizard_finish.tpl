{% extends "base.tpl" %}

{% block content %}

	<h1>{% trans %}We are done!{% endtrans %}</h1>

	<p>{% trans %}According to the tests, your voice range is:{% endtrans %}</p>

	<p class="center big">{{ your_voice|raw }}</p>

	<p>{% trans %}Congratulations! Now you can start to enjoy the automatic transpositions of Neo-Transposer.{% endtrans %}</p>

	<p class="center"><a href="{{ go_to_book }}" class="big flatbutton red">{% trans %}Transpose the songs{% endtrans %}</a></p>

{% endblock %}