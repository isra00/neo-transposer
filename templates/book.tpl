{% extends "base.tpl" %}

{% block meta_description %}Songs of the Neocatechumenal Way in {{ current_book.lang_name }}. Neo-Transposer automatically transposes the chords of the Way, so that they really fit your voice.{% endblock %}

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
		· <a href="{{ path('transpose_song', {"id_song":song.slug}) }}">{{ song.title }}</a>
	</li>
{% endfor %}
</ul>
{% endblock %}