<?php

namespace NeoTransposer\Domain\Entity;

use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * A song, with its voice ranges as NotesRange and its associated chords.
 */
class Song
{
    /** @todo Make all these private and create getters */
    public $idSong;
    public $idBook;
    public $page;
    public $title;
    public $range;
    public $slug;
    public $firstChordIsKey;

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

    public function __construct(?int $idSong, int $idBook, int $page, string $title, NotesRange $range, string $slug, bool $firstChordIsKey, ?NotesRange $peopleRange, ?string $bookChordPrinter, ?string $bookLocale, array $originalChords)
    {
        $this->idSong = $idSong;
        $this->idBook = $idBook;
        $this->page = $page;
        $this->title = $title;
        $this->range = $range;
        $this->slug = $slug;
        $this->firstChordIsKey = $firstChordIsKey;
        $this->peopleRange = $peopleRange;
        $this->bookChordPrinter = $bookChordPrinter;    //Used by TransposedSong
        $this->bookLocale = $bookLocale;                //Used by Controllers\TransposeSong
        $this->originalChords = $originalChords;
    }

    public static function fromDbColumns($dbColumns, $originalChords): Song
    {
        return new self(

            //From table song
            (int) $dbColumns['id_song'],
            (int) $dbColumns['id_book'],
            (int) $dbColumns['page'],
            $dbColumns['title'],
            new NotesRange($dbColumns['lowest_note'], $dbColumns['highest_note']),
            $dbColumns['slug'],
            (bool) $dbColumns['first_chord_is_tone'],
            (!empty($dbColumns['people_lowest_note']) && !empty($dbColumns['people_highest_note'])) ? new NotesRange($dbColumns['people_lowest_note'], $dbColumns['people_highest_note']) : null,

            //From table book
            $dbColumns['chord_printer'],
            $dbColumns['locale'],

            //From table song_chord
            $originalChords
        );
    }

    public function setOriginalChordsForPrint(ChordPrinter $chordPrinter) : void
    {
        $this->originalChordsForPrint = $chordPrinter->printChordset($this->originalChords);
    }

    public function idSong(): int
    {
        return $this->idSong;
    }

    public function idBook(): int
    {
        return $this->idBook;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function range(): NotesRange
    {
        return $this->range;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function firstChordIsKey(): bool
    {
        return $this->firstChordIsKey;
    }

    public function peopleRange(): ?NotesRange
    {
        return $this->peopleRange;
    }

    public function bookChordPrinter(): string
    {
        return $this->bookChordPrinter;
    }

    public function bookLocale(): string
    {
        return $this->bookLocale;
    }

    public function originalChords(): array
    {
        return $this->originalChords;
    }

    public function originalChordsForPrint(): array
    {
        return $this->originalChordsForPrint;
    }
}
