<?php include 'header.view.php' ?>

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

<?php include 'foot.view.php' ?>