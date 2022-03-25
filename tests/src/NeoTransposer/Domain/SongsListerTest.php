<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\BookNotExistException;
use NeoTransposer\Domain\SongsWithUserFeedbackCollection;
use NeoTransposer\Domain\SongsLister;
use NeoTransposer\Domain\SongRepository;
use NeoTransposer\Domain\UserRepository;
use NeoTransposer\Domain\UserNotExistException;
use NeoTransposer\Model\User;
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

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, [8 =>'test book']);
        $actualCollection = $this->sut->readBookSongsWithUserFeedback(8, 1);

        $this->assertEquals(
            new SongsWithUserFeedbackCollection($expected),
            $actualCollection
        );

        $this->assertEquals($expected, $actualCollection->asArray());
    }

    public function testReadSongsWithUserFeedbackInvalidUser()
    {
        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongsWithUserFeedback')
            ->willReturn(new SongsWithUserFeedbackCollection(['doesn`t matter']));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId')
            ->willReturn(new User('test@test.com', 1));

        $this->expectException(BookNotExistException::class);
        $this->expectExceptionMessage('The book #6 has not been found');

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, []);
        $this->sut->readBookSongsWithUserFeedback(6, 0);
    }

    public function testReadSongsWithUserFeedbackInvalidBook()
    {
        $mockedSongRepository = $this->createStub(SongRepository::class);
        $mockedSongRepository->method('readBookSongsWithUserFeedback')
            ->willReturn(new SongsWithUserFeedbackCollection(['doesn`t matter']));

        $mockedUserRepository = $this->createStub(UserRepository::class);
        $mockedUserRepository->method('readFromId')
            ->willReturn(null);

        $this->expectException(UserNotExistException::class);
        $this->expectExceptionMessage('The user #0 has not been found');

        $this->sut = new SongsLister($mockedSongRepository, $mockedUserRepository, []);
        $this->sut->readBookSongsWithUserFeedback(0, 0);
    }
}
