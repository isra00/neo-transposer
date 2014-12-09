<?php

define('SOFTWARE_NAME', 'Neo-Transposer');

define('DB_HOST',		'localhost');
define('DB_USER',		'root');
define('DB_PASSWORD',	'root');
define('DB_DATABASE',	'transposer');
define('DB_CHARSET',	'utf8');

define('DEFAULT_BOOK', '1');
define('DEFAULT_CHORD_PRINTER', 'English');

$GLOBALS['chord_printers'] = array(
	'English' => 'English (F#m, Bb7)',
	'Swahili' => 'Swahili (Fd-, Eb7)',
	'Spanish' => 'Española (Fa#-, Sib7)',
);

/******************************************************************************/

session_start();

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/src/'));

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_DATABASE);
mysql_query("SET character_set_client = '" . DB_CHARSET . "'");
mysql_query("SET character_set_connection = '" . DB_CHARSET . "'");
mysql_query("SET character_set_results = '" . DB_CHARSET . "'");

$books = array();
$q = mysql_query("SELECT * FROM book");
while ($book = mysql_fetch_assoc($q))
{
	$books[$book['id_book']] = $book['lang_name'];
}

$GLOBALS['books'] = $books;

if (!isset($_SESSION['lowest_note']) && false === array_search(basename($_SERVER['SCRIPT_NAME']), array('wizard.php', 'set_session.php')))
{
	header("Location: wizard.php");
}