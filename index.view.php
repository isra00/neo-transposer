<?php include 'header.view.php' ?>

<h1><?php echo $GLOBALS['books'][$current_book] ?></h1>

<ul class="song-index">
<?php foreach ($songs as $song) : ?>
	<li>
		<?php echo $song['page'] ?> ·
		<a href="transpose_song.php?song=<?php echo $song['id_song'] ?>"><?php echo $song['title'] ?></a>
	</li>
<?php endforeach ?>
</ul>

<?php include 'foot.view.php' ?>