<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{% if page_title %}{{ page_title }} · {{ neoglobals.software_name }}{% else %}{{ neoglobals.software_name }}{% endif %}</title>
	<link rel="stylesheet" href="{{ app.request.basepath }}/static/style.css" type="text/css" />
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<link rel="apple-touch-icon" href="favicon.ico">
</head>

<body class="{% block page_class %}{% endblock %}" {% block body_attributes %}{% endblock %}>

	{% if not neoglobals.debug %}
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '{{ neoglobals.analytics_id }}', 'auto');
	  ga('send', 'pageview');
	</script>
	{% endif %}

	<nav class="header">
		<div class="inside">

			{% if neoglobals.user.id_book %}
				<h2>
					<a href="{{ path('book', {id_book: (current_book ? current_book.id_book: neoglobals.user.id_book)}) }}">{{ neoglobals.software_name }}</a>
				</h2>
			{% else %}
				<h2>{{ neoglobals.software_name }}</h2>
			{% endif %}

			{% if neoglobals.here != 'login' %}
			<span class="user">
				<a href="{{ path('login') }}">Log-out</a>
			</span>
			{% endif %}

		</div>
	</nav>

	<section class="main">
	{% block content %}{% endblock %}
	</section>

	<footer>
		Developed in Tanzania. Write me suggestions or comments to isra00@gmail.com
	</footer>

</body>
</html>