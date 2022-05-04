<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\Book;
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

    public function readIdBookFromLocale(string $locale): int
    {
        return (int)$this->dbConnection->fetchColumn('SELECT id_book FROM book WHERE locale = ?', [$locale]);
    }

    public function readAllBooks(): array
    {
        $rows = $this->dbConnection->fetchAll('SELECT * FROM book ORDER BY lang_name');
        $booksNice = [];
        foreach ($rows as $row) {
            $booksNice[$row['id_book']] = new Book(
                $row['id_book'],
                $row['lang_name'],
                $row['details'],
                $row['chord_printer'],
                $row['locale'],
                $row['song_count']
            );
        }
        return $booksNice;
    }

    public function readBook(int $idBook): ?Book
    {
        $row = $this->dbConnection->fetchAll('SELECT * FROM book WHERE id_book = ?', [$idBook])[0];
        return new Book(
            $row['id_book'],
            $row['lang_name'],
            $row['details'],
            $row['chord_printer'],
            $row['locale'],
            $row['song_count']
        );
    }
}