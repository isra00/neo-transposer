<?php

namespace NeoTransposer;

include 'config.php';

$GLOBALS['chord_printers'] = array(
	'English' => 'English (F#m, Bb7)',
	'Swahili' => 'Swahili (Fd-, Eb7)',
	'Spanish' => 'Española (Fa#-, Sib7)',
);

define('START_TIME', microtime(true));

require 'vendor/autoload.php';

session_start();

//set_include_path(get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/src/'));

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_DATABASE);
mysql_query("SET character_set_client = '" . DB_CHARSET . "'");
mysql_query("SET character_set_connection = '" . DB_CHARSET . "'");
mysql_query("SET character_set_results = '" . DB_CHARSET . "'");

$books = array();
$q = mysql_query("SELECT * FROM book");
while ($book = mysql_fetch_assoc($q))
{
	$books[$book['id_book']] = $book;
}

$GLOBALS['books'] = $books;

if (!isset($_SESSION['user']))
{
	$_SESSION['user'] = new User;
}

if ($redirect = $_SESSION['user']->isRedirectionNeeded())
{
	header("Location: $redirect.php");
	die;
}