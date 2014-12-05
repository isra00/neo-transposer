<?php

include 'init.php';

$current_book = isset($_SESSION['book']) ? $_SESSION['book'] : DEFAULT_BOOK;

$q = mysql_query("SELECT * FROM song WHERE id_book = '$current_book' ORDER BY page, title");

$songs = array();
while ($song = mysql_fetch_assoc($q))
{
	$songs[] = $song;
}
unset($song);

include 'index.view.php';