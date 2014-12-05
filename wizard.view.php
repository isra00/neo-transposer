<?php include 'head.view.php' ?>

<body>
	<div class="main">

		<h1>Welcome to <?php echo SOFTWARE_NAME ?></h1>

		<div class="main">

			<form method="get" action="set_session.php">
				<p>This software analyses the songs and your voice, giving you the perfect
				transposition for each song, according to your voice. But to do so, first
				we need to set your voice settings and your preferences for the songs.</p>

				<h2>1. Which is the lowest note that you can sing? And the highest one?</h2>

				<p>
					Lowest:
					<select name="lowest_note">
					<?php foreach ($scale as $note) : ?>
						<option value="<?php echo $note ?>"><?php echo $note ?></option>
					<?php endforeach ?>
					</select>

					Highest:
					<select name="highest_note">
					<?php foreach ($scale as $note) : ?>
						<option value="<?php echo $note ?>"><?php echo $note ?></option>
					<?php endforeach ?>
					</select>
				</p>

				<h2>Which songbook do you want to transport?</h2>

				<select name="book">
				<?php foreach ($GLOBALS['books'] as $id=>$book) : ?>
					<option value="<?php echo $id ?>"><?php echo $book ?></option>
				<?php endforeach ?>
				</select>

				<input type="hidden" name="redirect" value="index.php" />

				<p class="center">
					<button type="submit" value="sent" class="bigbutton">We are ready!</button>
				</p>
			</form>
		</div>

<?php include 'foot.view.php' ?>