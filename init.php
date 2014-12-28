<?php

namespace NeoTransposer;

include 'config.php';

$GLOBALS['chord_printers'] = array(
	'English' => 'English (F#m, Bb7)',
	'Swahili' => 'Swahili (Fd-, Eb7)',
	'Spanish' => 'Español (Fa#-, Sib7)',
);

require 'vendor/autoload.php';

ini_set('session.cookie_lifetime', 60 * 60 * 24 * 31); //1 month.
session_start();

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_DATABASE);
mysql_query("SET character_set_client = '" . DB_CHARSET . "'");
mysql_query("SET character_set_connection = '" . DB_CHARSET . "'");
mysql_query("SET character_set_results = '" . DB_CHARSET . "'");

/** @todo Sacar esto de aquí */
$GLOBALS['books'] = array();
$q = mysql_query("SELECT * FROM book");
while ($book = mysql_fetch_assoc($q))
{
	$GLOBALS['books'][$book['id_book']] = $book;
}

if (!isset($_SESSION['user']))
{
	$_SESSION['user'] = new User;
}

if ($redirect = $_SESSION['user']->isRedirectionNeeded())
{
	header("Location: $redirect.php");
	die;
}