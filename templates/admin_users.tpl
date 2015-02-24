{% extends "base.tpl" %}

{% block page_class %}admin-users{% endblock %}

{% block content %}

<h2>Global performance</h2>

<h3>Good users (voice range > 1oct): {{ ((good_users / users|length) * 100)|round }}% </h3>
<div class="feedback-graph">
	<span class="yes" style="width: {{ ((good_users / users|length) * 100)|round }}px">{{ good_users }}</span>
	<span class="no" style="width: {{ (((users|length - good_users) / users|length) * 100)|round }}px">{{ users|length - good_users }}</span>
</div>

<h3>Feedback</h3>
{% for group, gp in global_performance %}
<p>
	{{ group }}: {{ ((gp.yes / gp.total) * 100)|round }}% 
	<div class="feedback-graph">
		{% if gp.yes %}<span class="yes" style="width: {{ ((gp.yes / gp.total) * 100)|round }}px">{{ gp.yes }}</span>{% endif %}
		{% if gp.no %}<span class="no" style="width: {{ ((gp.no / gp.total) * 100)|round }}px">{{ gp.no }}</span>{% endif %}
	</div>
</p>
{% endfor %}

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
			<td>{{ user.email }}</td>
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

{% endblock %}