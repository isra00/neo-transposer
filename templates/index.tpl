{% extends "base.tpl" %}

{% block page_class %}index{% endblock %}

{% block content %}
<h1>
	<small><a href="wizard.php#book">Change book</a></small>
	{{ current_book.lang_name }} ({{ current_book.details }})
</h1>

<ul class="song-index">
{% for song in songs %}
	<li>
		{{ song.page }}
		· <a href="transpose_song.php?song={{ song.id_song}}">{{ song.title }}</a>
	</li>
{% endfor %}
</ul>
{% endblock %}