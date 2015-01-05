<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\AutomaticTransposer;
use \NeoTransposer\TranspositionChart;
use \NeoTransposer\NotesCalculator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransposeSong
{
	public function get(\NeoTransposer\NeoApp $app, $id_song)
	{
		$field_id = 'slug';

		if (preg_match('/^\d+$/', $id_song))
		{
			$field_id = 'id_song';
			$id_song = (int) $id_song;
		}

		$song_details = $app['db']->fetchAssoc(
			"SELECT * FROM song JOIN book ON song.id_book = book.id_book WHERE $field_id = ?",
			array($id_song)
		);

		if (!$song_details) {
			$app->abort(404, "The specified song does not exist or it's not bound to a valid book");
		}

		$original_chords = $app['db']->fetchAll(
			'SELECT chord FROM song_chord JOIN song ON song_chord.id_song = song.id_song WHERE song.id_song = ? ORDER BY position ASC',
			array($song_details['id_song'])
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
		$your_voice = $app['user']->getVoiceAsString();

		return $app->render('transpose_song.tpl', array(
			'current_book'		=> $song_details,
			'song_details'		=> $song_details,
			'transpositions'	=> $transpositions,
			'not_equivalents'	=> $not_equivalents,
			'your_voice'		=> $your_voice,
			'original_chords'	=> $original_chords,
			'voice_chart'		=> TranspositionChart::getChart($song_details, $transpositions[0], $app['user']),
			'page_title'		=> $song_details['title'],
			'page_class'		=> 'transpose-song',
			'meta_canonical'	=> $app['url_generator']->generate(
				'transpose_song',
				array('id_song' => $song_details['slug']),
				UrlGeneratorInterface::ABSOLUTE_URL
			),
		));
	}
}