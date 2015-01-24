{% extends "base.tpl" %}

{% block page_class %}page-login{% endblock %}
{% block body_attributes %}onload="document.getElementById('email').focus()"{% endblock %}

{% block content %}

	<h1>{% trans with {'%software%': '<span class="software-name">' ~ neoglobals.software_name ~ '</span>'} %}Welcome to %software%{% endtrans %}</h1>

	{% macro login_form() %}
	{# @todo Implementar redirección después del login #}
	<form method="post" action="{{ path('login') }}" class="login-form">
		{% if form_error %}<div class="error">{{ form_error }}</div>{% endif %}
		
		<div class="field block full-width">
			<label for="email">{% trans %}Please, type your e-mail:{% endtrans %}</label>
			<input type="email" name="email" id="email" value="{{ post.email }}" required>
		</div>
		<div class="field block full-width">
			<button type="submit" name="sent" class="bigbutton">{% trans %}Enter{% endtrans %}</button>
		</div>
	</form>
	{% endmacro %}
	{% import _self as self %}

	{{ self.login_form() }}

{% endblock %}