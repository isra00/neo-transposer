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

{% if app.locale == 'es' and app.user.isLoggedIn %}
<aside class="book-warning">
	<h3>Aviso sobre el libro de cantos</h3>
	<p>Los títulos y los acordes de los cantos a continuación están tomados de
	la 20ª edición del Resucitó, editado en Madrid en 2014. Te sugiero
	encarecidamente que uses la <strong>edición oficial</strong> del libro de cantos,
	y no otras ediciones “piratas”, que pueden contener errores o diferencias en letras y acordes.
	</p>
</aside>
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