{% extends "base.tpl" %}

{% block page_class %}page-login{% endblock %}

{% block content %}

	<div class="mkt">
		<h2>
			<div class="inside">
				{% trans %}<span>It's not hard to transpose the song. What is hard is</span><span>to know to which tone should I transpose it <strong>for my voice</strong>.</span>{% endtrans %}
			</div>
		</h2>
	</div>

	<div class="inside">
		<p class="mkt-text">{% trans %}Neo-Transposer calculates the perfect transposition for each song of the Way based on your own voice. That simple. It also offers you alternatives to play the song with the easiest chords. No more complications!{% endtrans %}</p>

		{% macro login_form(error_msg) %}
		{# @todo Implementar redirección después del login #}
		<form method="post" action="{{ path('login') }}" class="login-form">
			<div class="error">{{ error_msg }}</div>
			
			<div class="field block full-width">
				<label for="email">{% trans %}Please, type your e-mail:{% endtrans %}</label>
				<input type="email" name="email" id="email" value="{{ post.email }}" autofocus required>
			</div>
			<div class="field block full-width">
				<button type="submit" name="sent" class="bigbutton">{% trans %}Enter{% endtrans %}</button>
			</div>
		</form>
		{% endmacro %}
		{% import _self as self %}
		{{ self.login_form(error_msg) }}

		<div class="lang-switch">
		{% for lang in languages %}
			{% if not loop.first %} · {% endif %}
			{% if lang == app.locale %}
				{{ language_names[lang] }}
			{% else %}
				<a href="{{ path(current_route, {'_locale': lang}) }}">{{ language_names[lang] }}</a>
			{% endif %}
		{% endfor %}
		</div>
	</div>

{% endblock %}