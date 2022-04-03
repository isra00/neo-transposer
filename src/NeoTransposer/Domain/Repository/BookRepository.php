<?php

namespace NeoTransposer\Domain\Repository;

interface BookRepository
{
    public function readBookLangFromId(int $idBook): string;
}