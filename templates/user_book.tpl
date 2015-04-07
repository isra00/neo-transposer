{% extends "base.tpl" %}

{% block page_class %}user-settings{% endblock %}

{% block content %}

<h1>{% trans %}Choose language{% endtrans %}</h1>

<ul class="big">
{% for id, book in app.books %}
	<li><a href="{{ path('book_' ~ id) }}">{{ book.lang_name }} ({{ book.details}})</a></li>
{% endfor %}
</ul>

{% endblock %}