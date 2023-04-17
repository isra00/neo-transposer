<?php

namespace NeoTransposer\Domain\Entity;

use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * A song, with its voice ranges as NotesRange and its associated chords.
 */
class Song
{
    /** @todo Make all these protected and create getters */
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

    public $originalChords;
    public $originalChordsForPrint = [];

    public function __construct($dbColumns, $originalChords)
    {
        //From table song
        $this->idSong           = $dbColumns['id_song'];
        $this->idBook           = $dbColumns['id_book'];
        $this->page             = $dbColumns['page'];
        $this->title            = $dbColumns['title'];
        $this->range            = new NotesRange($dbColumns['lowest_note'], $dbColumns['highest_note']);
        $this->slug             = $dbColumns['slug'];
        $this->firstChordIsTone = $dbColumns['first_chord_is_tone'];
        $this->peopleRange      = (!empty($dbColumns['people_lowest_note']) && !empty($dbColumns['people_highest_note'])) ? new NotesRange($dbColumns['people_lowest_note'], $dbColumns['people_highest_note']) : null;

        //From table book
        $this->bookChordPrinter = $dbColumns['chord_printer']; //Used by TransposedSong
        $this->bookLocale       = $dbColumns['locale'];        //Might be unused

        //From table song_chord
        $this->originalChords   = $originalChords;
    }

    public function setOriginalChordsForPrint(ChordPrinter $chordPrinter) : void
    {
        $this->originalChordsForPrint = $chordPrinter->printChordset($this->originalChords);
    }
}
