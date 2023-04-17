<?php

namespace NeoTransposer\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'book')]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', name: 'id_book')]
    #[ORM\GeneratedValue]
    protected $idBook;

    #[ORM\Column(type: 'string', name: 'lang_name')]
    protected $langName;

    #[ORM\Column(type: 'string')]
    protected $details;

    #[ORM\Column(type: 'string', name: 'chord_printer')]
    protected $chordPrinter;

    #[ORM\Column(type: 'string')]
    protected $locale;

    #[ORM\Column(type: 'integer', name: 'song_count')]
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

    public function chordPrinter(): string
    {
        return $this->chordPrinter;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function songCount(): int
    {
        return $this->songCount;
    }


}