<?php

namespace NeoTransposer\Domain\Entity;

use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * A song, with its voice ranges as NotesRange and its associated chords.
 */
class Song
{
    protected $idSong;

    protected $idBook;

    protected $page;

    protected $title;

    protected $range;

    protected $slug;

    protected $firstChordIsTone;

    protected $url;

    protected $artisticAdjustment;

    protected $idOriginSong;

    /**
     * When an admin checked the song against its book in the Songbook comparison.
     */
    protected $verifiedAt;

    /**
     * @var NotesRange|null
     */
    protected $peopleRange;

    protected $bookChordPrinter;

    protected $bookLocale;

    protected $originalChords;

    protected $originalChordsForPrint = [];

    public function __construct($dbColumns, $originalChords)
    {
        // From table song
        $this->idSong = $dbColumns['id_song'];
        $this->idBook = $dbColumns['id_book'];
        $this->page = $dbColumns['page'];
        $this->title = $dbColumns['title'];
        $this->range = new NotesRange($dbColumns['lowest_note'], $dbColumns['highest_note']);
        $this->slug = $dbColumns['slug'];
        $this->firstChordIsTone = $dbColumns['first_chord_is_tone'];
        $this->peopleRange = (!empty($dbColumns['people_lowest_note']) && !empty($dbColumns['people_highest_note'])) ? new NotesRange($dbColumns['people_lowest_note'], $dbColumns['people_highest_note']) : null;
        $this->url = $dbColumns['url'];
        $this->artisticAdjustment = $dbColumns['artistic_adjustment'];
        $this->idOriginSong = $dbColumns['id_origin_song'] ?? null;
        $this->verifiedAt = $dbColumns['verified_at'] ?? null;

        // From table book
        $this->bookChordPrinter = $dbColumns['chord_printer']; // Used by TransposedSong
        $this->bookLocale = $dbColumns['locale'];        // Might be unused

        // From table song_chord
        $this->originalChords = $originalChords;
    }

    public function setOriginalChordsForPrint(ChordPrinter $chordPrinter): void
    {
        $this->originalChordsForPrint = $chordPrinter->printChordset($this->originalChords);
    }

    public function idSong()
    {
        return $this->idSong;
    }

    public function idBook()
    {
        return $this->idBook;
    }

    public function page()
    {
        return $this->page;
    }

    public function title()
    {
        return $this->title;
    }

    public function range(): NotesRange
    {
        return $this->range;
    }

    public function slug()
    {
        return $this->slug;
    }

    public function firstChordIsTone()
    {
        return $this->firstChordIsTone;
    }

    public function url()
    {
        return $this->url;
    }

    public function artisticAdjustment()
    {
        return $this->artisticAdjustment;
    }

    public function idOriginSong()
    {
        return $this->idOriginSong;
    }

    public function verifiedAt()
    {
        return $this->verifiedAt;
    }

    public function peopleRange(): ?NotesRange
    {
        return $this->peopleRange;
    }

    public function bookChordPrinter()
    {
        return $this->bookChordPrinter;
    }

    public function bookLocale()
    {
        return $this->bookLocale;
    }

    public function originalChords()
    {
        return $this->originalChords;
    }

    public function originalChordsForPrint()
    {
        return $this->originalChordsForPrint;
    }
}
