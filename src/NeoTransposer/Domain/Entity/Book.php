<?php

namespace NeoTransposer\Domain\Entity;

class Book
{
    protected $idBook;
    protected $langName;
    protected $details;
    protected $chordPrinter;
    protected $locale;
    protected $songCount;

    public function __construct(int $idBook, string $langName, string $details, string $chordPrinter, string $locale, int $songCount)
    {
        $this->idBook       = $idBook;
        $this->langName     = $langName;
        $this->details      = $details;
        $this->chordPrinter = $chordPrinter;
        $this->locale       = $locale;
        $this->songCount    = $songCount;
    }
}