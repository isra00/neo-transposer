{% extends "base.tpl" %}

{% block page_class %}page-book{% endblock %}

{% block content %}
<h1>
	<span class="change-book"><a class="small-button" href="{{ path('user_settings') }}#book">Change book</a></span>
	{{ current_book.lang_name }}
	<small class="book-details">({{ current_book.details }})</small>
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