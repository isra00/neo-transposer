<?php

namespace NeoTransposer\Model;

class Song
{
	public $idSong;
	public $idBook;
	public $page;
	public $title;
	public $key;
	public $lowestNote;
	public $highestNote;
	public $slug;
	public $firstChordIsTone;
	public $bookChordPrinter;
	public $bookLocale;
	public $originalChords = array();

	public function __construct($dbColumns, $originalChords)
	{
		$this->idSong 				= $dbColumns['id_song'];
		$this->idBook 				= $dbColumns['id_book'];
		$this->page 				= $dbColumns['page'];
		$this->title 				= $dbColumns['title'];
		$this->key 					= $dbColumns['key'];
		$this->lowestNote 			= $dbColumns['lowest_note'];
		$this->highestNote 			= $dbColumns['highest_note'];
		$this->slug 				= $dbColumns['slug'];
		$this->firstChordIsTone		= $dbColumns['first_chord_is_tone'];
		$this->bookChordPrinter 	= $dbColumns['chord_printer'];
		$this->bookLocale 			= $dbColumns['locale'];

		$this->originalChords 		= $originalChords;
	}
}