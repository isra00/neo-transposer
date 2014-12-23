<?php

include 'init.php';

if (!isset($_GET['book']))
{
	header("Location: wizard.php");
}

$current_book = isset($_GET['book']) ? intval($_GET['book']) : $_SESSION['book'];

$q = mysql_query("SELECT * FROM song WHERE id_book = '$current_book' ORDER BY page, title");

$songs = array();
while ($song = mysql_fetch_assoc($q))
{
	$songs[] = $song;
}
unset($song);

$page_class = 'index';
include 'index.view.php';