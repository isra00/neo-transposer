{% extends "base.tpl" %}

{% block page_class %}page-book{% endblock %}

{% block content %}

{# @todo JS: Enfocar el campo title al cargar la página #}

	<h1>Insert song</h1>

	<form method="post" action="{{ neoglobals.server.REQUEST_URI }}">

		<p>
			<label for="book">Book:</label>
			<select name="id_book" id="book">
			{% for id, book in neoglobals.books %}
				<option value="{{ id }}" {% if id == current_book %}selected="selected"{% endif %}>{{ book.lang_name }}</option>
			{% endfor %}
			</select>
		</p>

		<p>
			<label for="title">Title:</label>
			<input name="title" id="title" size="50">
		</p>
		
		<p>
			<label for="page">Page:</label>
			<input name="page" id="page" size="3">
		</p>

		<p>
			<label for="lowest_note">Lowest note:</label>
			<input name="lowest_note" id="lowest_note" size="3">
		</p>

		<p>
			<label for="highest_note">Highest note:</label>
			<input name="highest_note" id="highest_note" size="3">
		</p>

		<p>
			<label>Chords:</label>
			{% for i in 0..9 %}
			<input name="chords[{{ i }}]" size="3"/> 
			{% endfor %}
		</p>

		<p><button type="submit" name="sent" class="bigbutton">Insert</button></p>

	</form>
{% endblock %}