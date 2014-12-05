<?php include 'head.view.php' ?>

<body>
	<div class="header">
		<h2><a href="index.php"><?php echo SOFTWARE_NAME ?></a></h2>

		<div class="session">

			<span class="user">
				(<a href="wizard.php"><?php echo $_SESSION['lowest_note'] . ' &rarr; ' . $_SESSION['highest_note'] ?></a>)
			</span>

			<?php $checked_book = ($_SESSION['book']) ? $_SESSION['book'] : DEFAULT_BOOK ?>
			<select name="book" onchange="location.href='set_session.php?book=' + this.value + '&redirect=<?php echo urlencode('index.php') ?>'">
			<?php foreach ($GLOBALS['books'] as $id=>$book) : ?>
				<option value="<?php echo $id ?>" <?php if ($id == $checked_book) echo 'selected="selected"' ?>><?php echo $book ?></option>
			<?php endforeach ?>
			</select>

			<?php $checked_printer = ($_SESSION['chord_printer']) ? $_SESSION['chord_printer'] : DEFAULT_CHORD_PRINTER ?>
			<select name="chord_printer" onchange="location.href='set_session.php?chord_printer=' + this.value + '&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>'">
			<?php foreach ($GLOBALS['chord_printers'] as $class=>$name) : ?>
				<option value="<?php echo $class ?>" <?php if ($class == $checked_printer) echo 'selected="selected"' ?>><?php echo $name ?></option>
			<?php endforeach ?>
			</select>

		</div>
	</div>

	<div class="main">