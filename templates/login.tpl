{% extends "base.tpl" %}

{% block page_class %}login{% endblock %}

{% block content %}

	<h1>Welcome to {{ neoglobals.software_name }}</h1>

	{% if form_error %}<div class="error">{{ form_error }}</div>{% endif %}

	<form method="post" action="{{ neoglobals.server.REQUEST_URI }}">
		<span class="field">
			Please, type your e-mail:
			<input name="email" value="{{ post.email }}">
		</span>
		<span class="field">
			<button type="submit" name="sent">Go</button>
		</span>
	</form>

{% endblock %}