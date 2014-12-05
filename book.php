<?php

mysql_connect("localhost", "root", "root");
mysql_select_db("transposer");

?>

<h1>Transposer 0.1</h1>

<?php if (!intval($_GET['book'])) : ?>

<p>Select your language:</p>
<ul><?php


$q = mysql_query("SELECT * FROM book");
while ($book = mysql_fetch_assoc($q))
{
	echo "<li><a href='book.php?book=" . $book['id_book'] . "'>" . $book['lang_name'] . "</a></li>\n";
}

?></ul>

<?php else : ?>

<?php 

$id_book = intval($_GET['book']);
$q_book = mysql_query("SELECT * FROM book WHERE id_book = '$id_book'");
$book = mysql_fetch_assoc($q_book);

?>

<h2><?php echo $book['lang_name'] ?></h2>
<ul>
<?php

$q = mysql_query("SELECT * FROM song WHERE id_book = '$id_book'");
while ($song = mysql_fetch_assoc($q))
{
	echo "<li><strong>" . $song['page'] . ":</strong> <a href='transpose_song.php?song=" . $song['id_song'] . "'>" . $song['title'] . "</a></li>\n";
}

?>
</ul>
<?php endif ?>