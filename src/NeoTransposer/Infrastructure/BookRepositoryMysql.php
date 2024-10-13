<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\Book;
use NeoTransposer\Domain\Repository\BookRepository;

final class BookRepositoryMysql extends MysqlRepository implements BookRepository
{
    public function readBookLangFromId(int $idBook): string
    {
        return $this->dbConnection->fetchOne(
            'SELECT lang_name FROM book WHERE id_book = ?',
            [$idBook]
        );
    }

    public function readIdBookFromLocale(string $locale): int
    {
        return $this->entityManager
            ->createQuery('SELECT b FROM ' . Book::class . ' b WHERE b.locale = ?1')
            ->setParameter(1, $locale)
            ->getResult()[0]
            ->idBook();
    }

    public function readAllBooks(): array
    {
        $rows = $this->dbConnection->select('SELECT * FROM book ORDER BY lang_name');
        $booksNice = [];
        foreach ($rows as $row) {
            $row = (array)$row;
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
        $row = (array)$this->dbConnection->select('SELECT * FROM book WHERE id_book = ?', [$idBook])[0];
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
