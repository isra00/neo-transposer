<?php

namespace NeoTransposer\Model;

use NeoTransposer\Model\ChordPrinter\ChordPrinter;

/**
 * A song, with its voice ranges as NotesRange and its associated chords.
 */
class Song
{
    public $idSong;
    public $idBook;
    public $page;
    public $title;
    public $range;
    public $slug;
    public $firstChordIsTone;

    /**
     * @var NotesRange|null
     */
    public $peopleRange;

    public $bookChordPrinter;
    public $bookLocale;
    
    /**
     * @var array
     */
    public $originalChords = [];
    public $originalChordsForPrint = [];

    public function __construct($dbColumns, $originalChords)
    {
        $this->idSong             = $dbColumns['id_song'];
        $this->idBook             = $dbColumns['id_book'];
        $this->page             = $dbColumns['page'];
        $this->title             = $dbColumns['title'];
        $this->range             = new NotesRange($dbColumns['lowest_note'], $dbColumns['highest_note']);
        $this->slug             = $dbColumns['slug'];
        $this->firstChordIsTone    = $dbColumns['first_chord_is_tone'];
        $this->peopleRange        = (!empty($dbColumns['people_lowest_note']) && !empty($dbColumns['people_highest_note'])) ? new NotesRange($dbColumns['people_lowest_note'], $dbColumns['people_highest_note']) : null;
        $this->bookChordPrinter = $dbColumns['chord_printer'];
        $this->bookLocale         = $dbColumns['locale'];

        $this->originalChords    = $originalChords;
    }

    public function setOriginalChordsForPrint(ChordPrinter $chordPrinter) : void
    {
        $this->originalChordsForPrint = $chordPrinter->printChordset($this->originalChords);
    }
}
