<?php

namespace NeoTransposer;

include 'init.php';

if (!intval($_GET['song'])) die("Please specify as song as argument");

$id_song = intval($_GET['song']);

$q = mysql_query("SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE id_song = $id_song");

if (!mysql_num_rows($q)) {
	die("404 not found: el canto $id_song no existe o no está asociado a ningún book");
}

$song_details = mysql_fetch_assoc($q);

$q = mysql_query("SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = '$id_song' ORDER BY position ASC");

$original_chords = array();
while ($row = mysql_fetch_assoc($q))
{
	$original_chords[] = $row['chord'];
}

$printer = isset($_SESSION['user']->chord_printer) ? $_SESSION['user']->chord_printer : DEFAULT_CHORD_PRINTER;
$printer = "\NeoTransposer\ChordPrinter\ChordPrinter$printer";

$transposer = new AutomaticTransposer();

$transpositions = $transposer->findTranspositions(
	$_SESSION['user']->lowest_note,
	$_SESSION['user']->highest_note,
	$song_details['lowest_note'], 
	$song_details['highest_note'], 
	$original_chords
);

/**
 * @todo  IMPORTANTE!!! Si la transposición perfecta es lo mismo que en el libro,
 *        mostrar "Cantar como en el libro", y de forma secundaria, transposiciones
 *        alternativas.
 */

$printer = new $printer();

$original_chords = $printer->printChordset($original_chords, true);

foreach ($transpositions as &$transposition)
{
	$transposition = $printer->printTransposition($transposition, true);
}
unset($transposition);

//Prepare the voice chart

$voice_chart = TranspositionChart::getChart($song_details, $transpositions[0], $_SESSION['user']);

$current_book = $song_details['id_book'];
$page_title = $song_details['title'];
include 'transpose_song.view.php';
