{% extends "base.tpl" %}

{% block page_class %}page-book{% endblock %}

{% block content %}
<h1>{% trans %}Songs of the Neocatechumenal Way{% endtrans %}</h1>
<h3>
	{% if app.user.isLoggedIn %}
	<span class="change-book"><a class="small-button" href="{{ path('user_settings', {'_locale':app.locale}) }}#book">{% trans %}Other languages{% endtrans %}</a></span>
	{% endif %}

	{{ current_book.lang_name }}
	<small class="book-details">({{ current_book.details }})</small>
</h3>

{% if not app.user.isLoggedIn %}
	{% import "login.tpl" as login %}

	<div class="teaser">
		<div class="inside">
			<div class="more-inside">
				<p>{% trans %}Log-in now to transpose automatically the chords of the songs of the Neocatechumenal Way.{% endtrans %}</p>
				{{ login.login_form('', app.request.getRequestUri) }}
			</div>
		</div>
	</div>

{% endif %}

<ul class="song-index">
{% for song in songs %}
	<li>
		{{ song.page }}
		· <a href="{{ path('transpose_song', {"id_song":song.slug}) }}">{{ song.title }}</a>
	</li>
{% endfor %}
</ul>
{% endblock %}