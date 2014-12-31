{% extends "base.tpl" %}

{% block page_class %}page-login{% endblock %}
{% block body_attributes %}onload="document.getElementById('email').focus()"{% endblock %}

{% block content %}

	<h1>Welcome to <span class="software-name">{{ neoglobals.software_name }}</span></h1>

	{% if form_error %}<div class="error">{{ form_error }}</div>{% endif %}

	<form method="post" action="{{ neoglobals.server.REQUEST_URI }}" class="login-form">
		<div class="field block full-width">
			<label for="email">Please, type your e-mail:</label>
			<input type="text" name="email" id="email" value="{{ post.email }}">
		</div>
		<div class="field block full-width">
			<button type="submit" name="sent" class="bigbutton">Enter</button>
		</div>
	</form>

{% endblock %}