<!doctype html>
<html class="no-js" lang="{{ app.locale }}">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{% if page_title %}{{ page_title }} · {{ app.neoconfig.software_name }}{% else %}{{ app.neoconfig.software_name }}{% endif %}</title>
	<link rel="stylesheet" href="{{ app.request.basepath }}/static/style.css" type="text/css" />
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<link rel="apple-touch-icon" href="favicon.ico">

	<meta name="description" content="{% block meta_description %}{% trans %}Neo-Transposer automatically transposes the songs of the Neocatechumenal Way for you, so they fit your voice perfectly.{% endtrans %}{% endblock %}" />
	{% if meta_canonical %}<link rel="canonical" href="{{ meta_canonical }}" />{% endif %}

	{% if not app.neoconfig.debug %}
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '{{ app.neoconfig.analytics_id }}', 'auto');
	  ga('send', 'pageview');
	</script>
	{% endif %}
</head>

<body class="lang-{{ app.locale }} {% block page_class %}{% endblock %}">
	<div class="wrapper">

		<nav class="header">
			<div class="inside">

				{% if current_book %}
					<h2>
						<a href="{{ path('book_' ~ (current_book ? current_book.id_book: app.user.id_book)) }}">{{ app.neoconfig.software_name }}</a>
					</h2>
				{% else %}
					<h2>{{ app.neoconfig.software_name }}</h2>
				{% endif %}

				{% if app.user.isLoggedIn and current_route != 'login' %}
				<span class="user">
					<a href="{{ path('login', {_locale: app.locale}) }}">{% trans %}Log-out{% endtrans %}</a>
				</span>
				{% endif %}

			</div>
		</nav>

		<section class="main">

		{% block notifications %}
			{% if notifications.success %}
				{% for notification in notifications.success %}
				<div class="notification success">{{ notification }}</div>
				{% endfor %}
			{% endif %}
			{% if notifications.error %}
				{% for notification in notifications.error %}
				<div class="notification error">{{ notification }}</div>
				{% endfor %}
			{% endif %}
		{% endblock %}

		{% block content %}{% endblock %}
		</section>

		<div class="push"></div>
	</div>

	<footer>
		<p>{% trans with {'%email%': '<img src="' ~ app.request.basepath ~ '/static/feedback.gif" class="feedback" alt="Feedback" width="121" height="15">'}%}Developed in Tanzania. Contact: %email%{% endtrans %}</p>
	</footer>
</body>
</html>