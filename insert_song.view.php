<?php include 'head.view.php' ?>

<body onload="document.getElementById('title').focus()">

<div class="main">
	<h1>Insert song</h1>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">

		<p>
			<label for="book">Book:</label>
			<select name="book" id="book">
			<?php foreach ($GLOBALS['books'] as $id=>$book) : ?>
				<option value="<?php echo $id ?>" <?php if ($id == $current_book) echo 'selected="selected"' ?>><?php echo $book ?></option>
			<?php endforeach ?>
			</select>
		</p>

		<p>
			<label for="title">Title:</label>
			<input name="title" id="title" size="50">
		</p>
		
		<p>
			<label for="page">Page:</label>
			<input name="page" id="page" size="50">
		</p>

		<p>
			<label for="lowest_note">Lowest note:</label>
			<input name="lowest_note" id="lowest_note" size="3">
		</p>

		<p>
			<label for="highest_note">Highest note:</label>
			<input name="highest_note" id="highest_note" size="3">
		</p>

		<p>
			<label>Chords:</label>
			<?php for ($i = 0; $i < 10; $i++) : ?>
			<input name="chords[<?php echo $i ?>]" size="3"/> 
			<?php endfor ?>
		</p>

		<p><button type="submit" name="sent" class="bigbutton">Insert</button></p>

	</form>
</div>

<?php include 'foot.view.php' ?>