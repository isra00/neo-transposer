<?php

namespace NeoTransposerApp\Tests\Domain\Service;

use NeoTransposerApp\Domain\Exception\SlugAlreadyExistsException;
use NeoTransposerApp\Domain\Repository\BookRepository;
use NeoTransposerApp\Domain\Repository\SongRepository;
use NeoTransposerApp\Domain\Service\SongCreator;
use PHPUnit\Framework\TestCase;

class SongCreatorTest extends TestCase
{
    protected $sut;

    public function testSongIsCreatedSuccessfully()
    {
        $mockSongRepository = $this->createMock(SongRepository::class);
        $mockSongRepository->expects($this->once())
            ->method('slugAlreadyExists')
            ->with('some-title')
            ->willReturn(false);
        $mockSongRepository->expects($this->once())
            ->method('createSong')
            ->with(1, 2, 'Sömé tìtlê', 'A1', 'A2', 'B1', 'G2', false, 'some-title', ['A', 'B']);

        $mockBookRepository = $this->createMock(BookRepository::class);

        $sut = new SongCreator($mockSongRepository, $mockBookRepository);
        $sut->createSong(1, 2, 'Sömé tìtlê', 'A1', 'A2', 'B1', 'G2', false, ['A', 'B']);
    }

    public function testSlugAlreadyExists()
    {
        $mockSongRepository = $this->createMock(SongRepository::class);
        $mockSongRepository->expects($this->exactly(2))
            ->method('slugAlreadyExists')
            ->willReturnOnConsecutiveCalls(true, false);

        $mockBookRepository = $this->createMock(BookRepository::class);
        $mockBookRepository->expects($this->once())
            ->method('readBookLangFromId')
            ->willReturn('thelanguage');

        $mockSongRepository->expects($this->once())
            ->method('createSong')
            ->with(1, 2, 'Some title', 'A1', 'A2', 'B1', 'G2', false, 'some-title-thelanguage', ['A', 'B']);

        $sut = new SongCreator($mockSongRepository, $mockBookRepository);
        $sut->createSong(1, 2, 'Some title', 'A1', 'A2', 'B1', 'G2', false, ['A', 'B']);
    }

    public function testSlugAlreadyExistsAgain()
    {
        $mockSongRepository = $this->createMock(SongRepository::class);
        $mockSongRepository->expects($this->exactly(2))
            ->method('slugAlreadyExists')
            ->willReturn(true, true);

        $mockBookRepository = $this->createMock(BookRepository::class);
        $mockBookRepository->expects($this->once())
            ->method('readBookLangFromId');

        $sut = new SongCreator($mockSongRepository, $mockBookRepository);

        $this->expectException(SlugAlreadyExistsException::class);
        $sut->createSong(1, 2, 'Some title', 'A1', 'A2', 'B1', 'G2', false, ['A', 'B']);
    }
}
