<?php include 'head.view.php' ?>

<body class="page-wizard" onload="document.forms[0].email.select()">

	<?php /** @todo Quitar esto de aquí */ ?>
	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', '<?php echo ANALYTICS_ID ?>', 'auto');
	  ga('send', 'pageview');

	</script>

	<section class="main">

		<h1>Welcome to <?php echo SOFTWARE_NAME ?></h1>

		<?php if (isset($form_is_valid) && $form_is_valid === false) : ?>
			<div class="error">
			That e-mail doesn't look good. Please, re-type it.
			</div>
		<?php endif ?>

		<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<span class="field">
				Please, type your e-mail:
				<input name="email" value="<?php if (isset($_POST['email'])) echo $_POST['email'] ?>">
			</span>
			<span class="field">
				<button type="submit" name="sent">Go</button>
			</span>
		</form>
	</section>

<?php include 'foot.view.php' ?>