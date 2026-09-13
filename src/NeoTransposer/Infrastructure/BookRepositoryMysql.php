<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\Book;
use NeoTransposer\Domain\Repository\BookRepository;

final class BookRepositoryMysql extends MysqlRepository implements BookRepository
{
    public function readBookLangFromId(int $idBook): string
    {
        return $this->dbConnection->scalar(
            'SELECT lang_name FROM book WHERE id_book = ?',
            [$idBook]
        );
    }

    public function readIdBookFromLocale(string $locale): int
    {
        return (int) $this->dbConnection->scalar(
            'SELECT id_book FROM book WHERE locale = ?',
            [$locale]
        );
    }

    public function readAllBooks(): array
    {
        return $this->readBooks('SELECT * FROM book ORDER BY lang_name');
    }

    public function readPublishedBooks(): array
    {
        return $this->readBooks('SELECT * FROM book WHERE published = 1 ORDER BY lang_name');
    }

    public function readBook(int $idBook): ?Book
    {
        $rows = $this->dbConnection->select('SELECT * FROM book WHERE id_book = ?', [$idBook]);

        if (empty($rows)) {
            return null;
        }

        return $this->createBookObjectFromDbRow((array) $rows[0]);
    }

    /**
     * @return Book[] Indexed by id_book.
     */
    private function readBooks(string $sql): array
    {
        $books = [];

        foreach ($this->dbConnection->select($sql) as $row) {
            $row = (array) $row;
            $books[$row['id_book']] = $this->createBookObjectFromDbRow($row);
        }

        return $books;
    }

    private function createBookObjectFromDbRow(array $row): Book
    {
        return new Book(
            $row['id_book'],
            $row['lang_name'],
            $row['details'],
            $row['chord_printer'],
            $row['locale'],
            $row['song_count'],
            (bool) $row['published']
        );
    }
}
