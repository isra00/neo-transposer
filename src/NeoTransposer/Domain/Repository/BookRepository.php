<?php

namespace NeoTransposer\Domain\Repository;

interface BookRepository
{
    public function readBookLangFromId(int $idBook): string;
    public function readAllBooks(): array;
    public function readIdBookFromLocale(string $locale): int;
}