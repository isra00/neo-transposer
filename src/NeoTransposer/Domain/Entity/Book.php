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

    protected $published;

    public function __construct(int $idBook, string $langName, string $details, string $chordPrinter, string $locale, int $songCount, bool $published)
    {
        $this->idBook = $idBook;
        $this->langName = $langName;
        $this->details = $details;
        $this->chordPrinter = $chordPrinter;
        $this->locale = $locale;
        $this->songCount = $songCount;
        $this->published = $published;
    }

    public function idBook(): int
    {
        return $this->idBook;
    }

    public function langName(): string
    {
        return $this->langName;
    }

    public function details(): string
    {
        return $this->details;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Unpublished books (and their songs) are hidden from the public interface.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }
}
