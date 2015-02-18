<!doctype html>
<html class="no-js" lang="{{ app.locale }}">
<head>
	<meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{{ page_title }}</title>
	<link rel="stylesheet" href="{{ app.request.basepath }}/static/style{% if not app.debug %}.min{% endif %}.css" type="text/css" />
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<link rel="apple-touch-icon" href="favicon.ico">

	<meta name="description" content="{% block meta_description %}{% trans %}Neo-Transposer automatically transposes the songs of the Neocatechumenal Way for you, so they fit your voice perfectly.{% endtrans %}{% endblock %}" />
	{% if meta_canonical %}<link rel="canonical" href="{{ meta_canonical }}" />{% endif %}

	{% if not app.debug %}
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '{{ app.neoconfig.analytics_id }}', 'auto');
	  
	  {% if app.user.isLoggedIn -%}
	  ga('set', 'dimension1', '{{ app.user.id_user }}');
	  {%- endif %}

	  ga('send', 'pageview', );
	</script>
	{% endif %}
</head>

<body class="lang-{{ app.locale }} {% block page_class %}{% endblock %}">
	<div class="wrapper">

		<nav class="header">
			<div class="inside">

				{% if current_book -%}
					<h2>
						<a href="{{ path('book_' ~ (current_book ? current_book.id_book: app.user.id_book)) }}">{{ app.neoconfig.software_name }}</a>
					</h2>
				{%- else -%}
					<h2>{{ app.neoconfig.software_name }}</h2>
				{%- endif %}

				{% if app.user.isLoggedIn -%}
				<span class="user">
					<a href="{{ path('login', {_locale: app.locale}) }}">{% trans %}Log-out{% endtrans %}</a>
				</span>
				{%- endif %}

				{#· {{ app.user.lowest_note }}
				· {{ app.user.highest_note }}#}

			</div>
		</nav>

		<section class="main">

		{% if notifications.error %}
			{% for notification in notifications.error %}
			<div class="notification error">{{ notification }}</div>
			{% endfor %}
		{% endif %}
		{% if notifications.success %}
			{% for notification in notifications.success %}
			<div class="notification success">{{ notification }}</div>
			{% endfor %}
		{% endif %}

		{% block content %}{% endblock %}
		</section>

		<div class="push"></div>
	</div>

	<footer>
		<p>{% trans %}Developed in Tanzania.{% endtrans %} <a href="javascript:void(0)" onclick="go()">{%trans %}Contact{% endtrans %}</a></p>
	</footer>

	{% macro loadJsFramework() -%}
	<script src="{{ app.request.basepath }}/static/zepto.min.js"></script>
	{%- endmacro %}

	<script>
	//Un sencillo y comprensible método anti correo basura
	function go() {
		var s="el señor es mi pastor, nada me falta. En prados de fresca hierba...",
		 p=[14,4,8,17],p2=[13,25,14,34,37,56,46,29],n=parseInt(1-1);
		function join(ps){var r="";for(i in ps){r += s.substr(ps[i]-1,1);}return r;}
		alert(join(p)+n+n+String.fromCharCode(Math.pow(2,6))+'g'+join(p2));
	}
	</script>
</body>
</html>