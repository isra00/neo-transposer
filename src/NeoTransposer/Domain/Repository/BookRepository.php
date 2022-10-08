<?php

namespace NeoTransposer\Domain\Repository;

use NeoTransposer\Domain\Entity\Book;

interface BookRepository
{
    public function readBookLangFromId(int $idBook): string;
    public function readAllBooks(): array;
    public function readIdBookFromLocale(string $locale): int;
    public function readBook(int $idBook): ?Book;
}
