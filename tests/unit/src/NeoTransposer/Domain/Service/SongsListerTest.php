<?php

namespace NeoTransposer\Tests\Domain\Service;

use NeoTransposer\Domain\Entity\Book;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\UserNotExistException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\SongRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\Service\SongsLister;
use NeoTransposer\Domain\SongsCollection;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;
use PHPUnit\Framework\TestCase;

class SongsListerTest extends TestCase
{
    protected $app;

    protected $sut;

    public function setUp(): void
    {
        $this->app = new \Silex\Application();
    }

    public function testReadSongsWithUserFeedbackValidUser(): void
    {
        $expected = [[
            'id_song' => 1,
            'slug'    => 'test-song',
            'page'    => 1,
            'title'   => 'Test Song',
            'worked'  => 1,
        ]];

        $mockedSongRepository = $this->createMock(SongRepository::class);
        $mockedSongRepository->method('readBookSongsWithUserFeedback')
            ->with(8, 1)
            ->willReturn(new SongsWithUserFeedbackCollection($expected));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId')
            ->willReturn(new User('test@test.com', 1));

        $mockedBookRepo = $this->createMock(BookRepository::class);
        $mockedBookRepo->method('readBook')
            ->with(8)
            ->willReturn($this->createStub(Book::class));

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, $mockedBookRepo);
        $actualCollection = $this->sut->readBookSongsWithUserFeedback(8, 1);

        $this->assertEquals(
            new SongsWithUserFeedbackCollection($expected),
            $actualCollection
        );

        $this->assertEquals($expected, $actualCollection->asArray());
    }

    public function testReadSongsWithUserFeedbackInvalidBook()
    {
        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongsWithUserFeedback')
            ->willReturn(new SongsWithUserFeedbackCollection(['doesn`t matter']));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId')
            ->willReturn(new User('test@test.com', 1));

        $mockedBookRepo = $this->createStub(BookRepository::class);
        $mockedBookRepo->method('readBook')
            ->willReturn(null);

        $this->expectException(BookNotExistException::class);
        $this->expectExceptionMessage('The book #6 has not been found');

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, $mockedBookRepo);
        $this->sut->readBookSongsWithUserFeedback(6, 0);
    }

    public function testReadSongsWithUserFeedbackInvalidUser()
    {
        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongsWithUserFeedback')
            ->willReturn(new SongsWithUserFeedbackCollection(['doesn`t matter']));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId')
            ->willReturn(null);

        $this->expectException(UserNotExistException::class);
        $this->expectExceptionMessage('The user #0 has not been found');

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, $this->createStub(BookRepository::class));
        $this->sut->readBookSongsWithUserFeedback(0, 0);
    }

    public function testReadSongsValidBook()
    {
        $expected = [[
            'id_song' => 1,
            'slug'    => 'test-song',
            'page'    => 1,
            'title'   => 'Test Song'
        ]];

        $mockedSongRepository = $this->createMock(SongRepository::class);
        $mockedSongRepository->method('readBookSongs')
            ->with(8, 1)
            ->willReturn(new SongsCollection($expected));

        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongs')
            ->willReturn(new SongsCollection($expected));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId');

        $mockedBookRepository = $this->createMock(BookRepository::class);
        $mockedBookRepository->method('readBook')
            ->with(14)
            ->willReturn($this->createStub(Book::class));

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, $mockedBookRepository);
        $actualCollection = $this->sut->readBookSongs(14);

        $this->assertEquals(
            new SongsCollection($expected),
            $actualCollection
        );

        $this->assertEquals($expected, $actualCollection->asArray());
    }

    public function testReadSongsInvalidBook()
    {
        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongs')
            ->willReturn(new SongsCollection(['doesn`t matter']));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId');

        $mockedBookRepo = $this->createMock(BookRepository::class);
        $mockedBookRepo->method('readBook')
            ->willReturn(null);

        $this->expectException(BookNotExistException::class);
        $this->expectExceptionMessage('The book #14 has not been found');

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, $mockedBookRepo);
        $this->sut->readBookSongs(14);
    }
}
