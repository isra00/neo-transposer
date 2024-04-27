<?php

namespace NeoTransposer\Domain\Service;

use NeoTransposer\Domain\Exception\SlugAlreadyExistsException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;

final class SongCreator
{
    public function __construct(
        protected SongRepository $songRepository,
        protected BookRepository $bookRepository)
    {
    }

    public function createSong(
        int $idBook,
        ?int $page,
        string $title,
        string $lowestNote,
        string $highestNote,
        string $peopleLowestNote,
        string $peopleHighestNote,
        bool $firstChordIsNote,
        array $chords
    ): void {

        $this->songRepository->createSong(
            $idBook,
            $page,
            $title,
            $lowestNote,
            $highestNote,
            $peopleLowestNote,
            $peopleHighestNote,
            $firstChordIsNote,
            $this->getSlug($title, $idBook),
            $chords
        );
    }

	private function getSlug(string $title, int $idBook): string
	{
		$candidate = $this->urlize($title);
		$slugAlreadyExists = $this->songRepository->slugAlreadyExists($candidate);

		//If there is a song with the same slug, try to append the language name, or throw an exception
		if ($slugAlreadyExists)
		{
			$lang_name = $this->bookRepository->readBookLangFromId($idBook);
			$candidate = $candidate . '-' . $this->urlize($lang_name);
			$slugAlreadyExists = $this->songRepository->slugAlreadyExists($candidate);

			if ($slugAlreadyExists)
			{
				throw new SlugAlreadyExistsException('There is already a song with that slug in that book!');
			}
		}

		return $candidate;
	}

	private function urlize($string): string
	{
		$hyphenize = [' ', ',', '.', ':', '!', '¡', '¿', '?', '(', ')', '[', ']'];

		//La ñ la conservamos
		$flattenLetters = [
			'Á' => 'a',
			'À' => 'a',
			'Â' => 'a',
			'Ã' => 'a',
			'á' => 'a',
			'à' => 'a',
			'â' => 'a',
			'ã' => 'a',
			'É' => 'e',
			'È' => 'e',
			'Ê' => 'e',
			'é' => 'e',
			'è' => 'e',
			'ê' => 'e',
			'Í' => 'i',
			'Ì' => 'i',
			'Î' => 'i',
			'í' => 'i',
			'ì' => 'i',
			'î' => 'i',
			'Ó' => 'o',
			'Ò' => 'o',
			'Õ' => 'o',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'ö' => 'o',
			'õ' => 'o',
			'Ú' => 'u',
			'Ù' => 'u',
			'Û' => 'u',
			'ú' => 'u',
			'ù' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ª' => 'a',
			'º' => 'o',
        ];

		$string = strtolower(trim((string) $string));
		$string = str_replace($hyphenize, '-', $string);
		$string = str_replace(
			array_keys($flattenLetters),
			array_values($flattenLetters),
			$string
		);
		$string = preg_replace('/(\-\-+)/', '-', $string);
		$string = preg_replace('/^\-/', '', $string);
		return preg_replace('/\-$/', '', $string);
	}
}