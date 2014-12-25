<?php

include 'init.php';

if (isset($_GET['book']))
{
	if (false === array_search($_GET['book'], array_keys($GLOBALS['books'])))
	{
		die("Invalid request: the specified book does not exist");
	}

	$_SESSION['user']->id_book = intval($_GET['book']);

	// Auto-select chord printer based on book.
	if (!isset($_GET['chord_printer']))
	{
		$q = mysql_query("SELECT chord_printer FROM book WHERE id_book = '" . $_SESSION['user']->id_book . "'");
		$chord_printer = mysql_fetch_assoc($q);
		$_SESSION['user']->chord_printer = $chord_printer['chord_printer'];
	}
}

if (isset($_GET['chord_printer']))
{
	if (false === array_search($_GET['chord_printer'], array_keys($GLOBALS['chord_printers'])))
	{
		die("Invalid request: the specified chord notation does not exist");
	}

	$_SESSION['user']->chord_printer = $_GET['chord_printer'];
}

if (isset($_GET['lowest_note']))
{
	$_SESSION['user']->lowest_note = $_GET['lowest_note'];
}

if (isset($_GET['highest_note']))
{
	$_SESSION['user']->highest_note = $_GET['highest_note'];
}

$_SESSION['user']->persist();

$redirect = $_GET['redirect'] ? $_GET['redirect'] : 'index.php?book=' . $_SESSION['user']->id_book;
header('Location: ' . $redirect);