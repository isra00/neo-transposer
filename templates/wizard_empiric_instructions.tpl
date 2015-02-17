{% extends "base.tpl" %}

{% block page_class %}voice-wizard{% endblock %}

{% block content %}

	<h1>{% trans %}Step 2: time to sing!{% endtrans %}</h1>

	<p>{% trans %}Now I will propose you to sing the chorus of a song in a certain tone. Take your guitar and try to sing it in that tone.{% endtrans %}</p>

	<p>{% trans %}If it is too low for you, click on “No, it's too low”. If you were able to sing it, click on “Yes”. Then, I will transpose the song so it will be a bit lower, and you should try again. We will keep repeating the exercise until you can't sing lower{% endtrans %}</p>

	<p>{% trans %}The goal of this exercise is not to sing with a nice voice or comfortably, but to find the lowest possible note that you can sing. So please, make an effort, even if it is uncomfortable, so that I may see where is exactly the limit of your voice.{% endtrans %}</p>

	<p>{% trans with {'song_title': song_title} %}You have to sing the chorus, and <strong>only the chorus</strong>, of “%song_title%”.{% endtrans %}</p>

	<form method="post" action="{{ here }}">
		<p class="center"><button type="submit" value="sent" class="bigbutton">{% trans %}Understood{% endtrans %}</button></p>
	</form>

{% endblock %}