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

$transposer = new AutomaticTransposer(
	$_SESSION['user']->lowest_note,
	$_SESSION['user']->highest_note,
	$song_details['lowest_note'], 
	$song_details['highest_note'], 
	$original_chords
);

$transpositions = $transposer->getTranspositions();
$not_equivalents = $transposer->findAlternativeNotEquivalent();

//Prepare the chords nicely printed

$printer = isset($_SESSION['user']->chord_printer) ? $_SESSION['user']->chord_printer : DEFAULT_CHORD_PRINTER;
$printer = "\NeoTransposer\ChordPrinter\ChordPrinter$printer";
$printer = new $printer();

$original_chords = $printer->printChordset($original_chords);

foreach ($transpositions as &$transposition)
{
	$transposition = $printer->printTransposition($transposition);
}
foreach ($not_equivalents as &$transposition)
{
	$transposition = $printer->printTransposition($transposition);
}
unset($transposition);

$nc = new NotesCalculator;
$your_voice = array(
	'from' => $nc->getOnlyNote($_SESSION['user']->lowest_note),
	'to' => $nc->getAsOctaveDifference($_SESSION['user']->highest_note)
);

echo $twig->render('transpose_song.tpl', array(
	'current_book'		=> $song_details,
	'song_details'		=> $song_details,
	'transpositions'	=> $transpositions,
	'not_equivalents'	=> $not_equivalents,
	'your_voice'		=> $your_voice,
	'original_chords'	=> $original_chords,
	'voice_chart'		=> TranspositionChart::getChart($song_details, $transpositions[0], $_SESSION['user']),
	'page_title'		=> $song_details['title'],
	'page_class'		=> 'transpose-song',
));