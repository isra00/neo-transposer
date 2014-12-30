{% extends "base.tpl" %}

{% block page_class %}index{% endblock %}

{% block content %}
<h1>
	<small><a href="{{ path('user_settings') }}#book">Change book</a></small>
	{{ current_book.lang_name }} ({{ current_book.details }})
</h1>

<ul class="song-index">
{% for song in songs %}
	<li>
		{{ song.page }}
		· <a href="{{ path('transpose_song', {"id_song":song.id_song}) }}">{{ song.title }}</a>
	</li>
{% endfor %}
</ul>
{% endblock %}