<?php include 'head.view.php' ?>

<body>

	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '<?php echo ANALYTICS_ID ?>', 'auto');
	  ga('send', 'pageview');

	</script>

	<div class="header">
		<?php $current_book = isset($current_book) ? $current_book : $_SESSION['book'] ?>
		<h2><a href="index.php?book=<?php echo $current_book ?>"><?php echo SOFTWARE_NAME ?></a></h2>

		<div class="session">

			<span class="user">
				(<a href="wizard.php"><?php echo $_SESSION['lowest_note'] . ' &rarr; ' . $_SESSION['highest_note'] ?></a>)
			</span>

			<span class="switch-book">
				<select name="book" onchange="location.href='set_session.php?book=' + this.value">
				<?php foreach ($GLOBALS['books'] as $id=>$book) : ?>
					<option value="<?php echo $id ?>" <?php if ($id == $current_book) echo 'selected="selected"' ?>><?php echo $book ?></option>
				<?php endforeach ?>
				</select>
			</span>

			<span class="switch-printer">
				<?php $checked_printer = ($_SESSION['chord_printer']) ? $_SESSION['chord_printer'] : DEFAULT_CHORD_PRINTER ?>
				<select name="chord_printer" onchange="location.href='set_session.php?chord_printer=' + this.value + '&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>'">
				<?php foreach ($GLOBALS['chord_printers'] as $class=>$name) : ?>
					<option value="<?php echo $class ?>" <?php if ($class == $checked_printer) echo 'selected="selected"' ?>><?php echo $name ?></option>
				<?php endforeach ?>
				</select>
			</span>

		</div>
	</div>

	<div class="main">