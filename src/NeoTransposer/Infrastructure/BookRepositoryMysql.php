<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\BookRepository;

class BookRepositoryMysql extends MysqlRepository implements BookRepository
{

    public function readBookLangFromId(int $idBook): string
    {
        return $this->dbConnection->fetchColumn(
            'SELECT lang_name FROM book WHERE id_book = ?',
            [$idBook]
        );
    }
}