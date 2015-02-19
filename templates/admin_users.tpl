{% extends "base.tpl" %}

{% block content %}

<h2>Users ({{ users|length }})</h2>

<table class="data-table">
	<thead><tr>
		<th>E-mail</th>
		<th>Tesitura</th>
		<th>Book</th>
		<th>GeoIP</th>
		<th>Registered</th>
		<th>Fback</th>
	</tr></thead>
	<tbody>
{% for user in users %}
		<tr>
			<td>{{ user.email[:4] }}...</td>
			<td>{{ user.lowest_note }} - {{ user.highest_note }}</td>
			<td>{{ app.books[user.id_book].lang_name }}</td>
			<td>
				<img src="https://cdn1.iconfinder.com/data/icons/famfamfam_flag_icons/{{ user.country.isoCode|lower }}.png" width="16" />&nbsp;
				{{ user.country.names['en']|default('?') }}
			</td>
			<td>{{ user.register_time }}</td>
			<td>{{ user.feedback }}</td>
		</tr>
{% endfor %}
	</tbody>
</table>

<h2>Feedback ({{ feedback|length }})</h2>

<table class="data-table">
	<thead><tr>
		<th>Song</th>
		<th>Yes</th>
		<th>No</th>
	</tr></thead>

	<tbody>
{% for song in feedback %}
		<tr>
			<td>{{ song.title }}</td>
			<td>{{ song.yes }}</td>
			<td>{{ song.no }}</td>
		</tr>
{% endfor %}
	</tbody>
</table>

{% endblock %}