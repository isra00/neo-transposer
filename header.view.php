<!doctype html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo (isset($page_title) ? $page_title . ' · ' : '') . SOFTWARE_NAME ?></title>
	<link rel="stylesheet" href="style.css" type="text/css" />
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<link rel="apple-touch-icon" href="favicon.ico">
</head>

<body class="<?php if (isset($page_class)) echo $page_class ?>">

<!--<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo ANALYTICS_ID ?>', 'auto');
  ga('send', 'pageview');
</script>-->

<nav class="header">
	<div class="inside">
		<?php if (isset($_SESSION['user']->id_book)) : ?>
			<?php $current_book = isset($current_book) ? $current_book : $_SESSION['user']->id_book ?>
			<h2><a href="index.php?book=<?php echo $current_book ?>"><?php echo SOFTWARE_NAME ?></a></h2>
		<?php else : ?>
			<h2><?php echo SOFTWARE_NAME ?></h2>
		<?php endif ?>

		<?php if (isset($_SESSION['user']->id_user)) : ?>
		<span class="user">
			<a href="login.php">Log-out</a>
		</span>
		<?php endif ?>

	</div>
</nav>

<section class="main">