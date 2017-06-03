<?php

namespace NeoTransposer\Model;

class Song
{
	public $idSong;
	public $idBook;
	public $page;
	public $title;
	public $range;
	public $slug;
	public $firstChordIsTone;
	public $peopleRange;

	public $bookChordPrinter;
	public $bookLocale;
	public $originalChords = array();

	public function __construct($dbColumns, $originalChords)
	{
		$this->idSong 			= $dbColumns['id_song'];
		$this->idBook 			= $dbColumns['id_book'];
		$this->page 			= $dbColumns['page'];
		$this->title 			= $dbColumns['title'];
		$this->range 			= new NotesRange($dbColumns['lowest_note'], $dbColumns['highest_note']);
		$this->slug 			= $dbColumns['slug'];
		$this->firstChordIsTone	= $dbColumns['first_chord_is_tone'];
		$this->peopleRange		= (!empty($dbColumns['people_lowest_note']) && !empty($dbColumns['people_highest_note'])) ? new NotesRange($dbColumns['people_lowest_note'], $dbColumns['people_highest_note']) : null;
		$this->bookChordPrinter = $dbColumns['chord_printer'];
		$this->bookLocale 		= $dbColumns['locale'];

		$this->originalChords	= $originalChords;
	}
}
