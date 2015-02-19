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
		<th>Feedback</th>
	</tr></thead>

	<tbody>
{% for song in feedback %}
		<tr>
			<td>{{ song.title }}</td>
			<td>
				<div class="feedback-graph">
					{% if song.yes %}<span class="yes" style="width: {{ ((song.yes / song.total) * 100)|round }}px">{{ song.yes }}</span>{% endif %}
					{% if song.no %}<span class="no" style="width: {{ ((song.no / song.total) * 100)|round }}px">{{ song.no }}</span>{% endif %}
				</div>
			</td>
		</tr>
{% endfor %}
	</tbody>
</table>

{% endblock %}