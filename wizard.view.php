<?php include 'head.view.php' ?>

<body class="page-wizard">

	<?php /** @todo Quitar esto de aquí */ ?>
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '<?php echo ANALYTICS_ID ?>', 'auto');
	  ga('send', 'pageview');

	</script>

	<div class="main">

		<h1>Welcome to <?php echo SOFTWARE_NAME ?></h1>

		<form method="get" action="set_session.php">
			<p>This software analyses the songs and your voice, giving you the perfect
			transposition for each song, according to your voice. But to do so, first
			I need to know your voice.</p>

			<h2>1. Which is the lowest note that you can sing? And the highest one?</h2>

			<p class="voice-selector">
				<span class="field">
					Lowest:
					<select name="lowest_note">
					<?php foreach ($accoustic_scale as $note) : ?>
						<option value="<?php echo $note . '1' ?>" <?php if (isset($_SESSION['lowest_note']) && $_SESSION['lowest_note'] == $note) echo 'selected="selected"' ?>><?php echo $note ?></option>
					<?php endforeach ?>
					</select>
				</span>

				<span class="field">
					Highest:
					<select name="highest_note">
<?php foreach ($accoustic_scale as $note) : ?>
						<option value="<?php echo $note . '1' ?>" <?php if (isset($_SESSION['highest_note']) && $_SESSION['highest_note'] == $note . '1') echo 'selected="selected"' ?>><?php echo $note ?></option>
<?php endforeach ?>

<?php for ($i = 1; $i < 4; $i++) : ?>
						<optgroup label="+<?php echo $i ?> octave<?php if ($i > 1) echo 's' ?>">
<?php foreach ($accoustic_scale as $note) : ?>
							<option value="<?php echo $note . strval($i + 1) ?>" <?php if (isset($_SESSION['highest_note']) && $_SESSION['highest_note'] == $note . strval($i + 1)) echo 'selected="selected"' ?>><?php echo $note ?></option>
<?php endforeach ?>
						</optgroup>
<?php endfor ?>
					</select>
				</span>
			</p>

			<h2>Which songbook do you want to transpose?</h2>

			<select name="book">
			<?php foreach ($GLOBALS['books'] as $id=>$book) : ?>
				<option value="<?php echo $id ?>"><?php echo $book['lang_name'] . ' (' . $book['details'] . ')' ?></option>
			<?php endforeach ?>
			</select>

			<p class="center">
				<button type="submit" value="sent" class="bigbutton">We are ready!</button>
			</p>
		</form>

<?php include 'foot.view.php' ?>