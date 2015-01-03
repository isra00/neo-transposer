<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\AutomaticTransposer;
use \NeoTransposer\TranspositionChart;
use \NeoTransposer\NotesCalculator;

class TransposeSong
{
	public function get(\NeoTransposer\NeoApp $app, $id_song)
	{
		$song_details = $app['db']->fetchAssoc(
			'SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE id_song = ?',
			array((int) $id_song)
		);

		if (!$song_details) {
			$app->abort(404, "The specified song does not exist or it's not bound to a valid book");
		}

		$original_chords = $app['db']->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array((int) $id_song)
		);

		array_walk($original_chords, function(&$item) {
			$item = $item['chord'];
		});

		$transposer = new AutomaticTransposer(
			$app['user']->lowest_note,
			$app['user']->highest_note,
			$song_details['lowest_note'], 
			$song_details['highest_note'], 
			$original_chords
		);

		$transpositions = $transposer->getTranspositions();
		$not_equivalents = $transposer->findAlternativeNotEquivalent();

		//Prepare the chords nicely printed

		$printer = !empty($app['user']->chord_printer)
			? $app['user']->chord_printer
			: $app['neoconfig']['default_chord_printer'];

		$printer = $app['chord_printers.get']($printer);

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
			'from' => $nc->getOnlyNote($app['user']->lowest_note),
			'to' => $nc->getAsOctaveDifference($app['user']->highest_note)
		);

		$dumb_user = new \StdClass;
		$dumb_user->lowest_note = 'B1';
		$dumb_user->highest_note = 'G3';

		return $app->render('transpose_song.tpl', array(
			'current_book'		=> $song_details,
			'song_details'		=> $song_details,
			'transpositions'	=> $transpositions,
			'not_equivalents'	=> $not_equivalents,
			'your_voice'		=> $your_voice,
			'original_chords'	=> $original_chords,
			'voice_chart'		=> TranspositionChart::getChart($song_details, $transpositions[0], $dumb_user),
			//'voice_chart'		=> TranspositionChart::getChart($song_details, $transpositions[0], $app['user']),
			'page_title'		=> $song_details['title'],
			'page_class'		=> 'transpose-song',
		));
	}
}