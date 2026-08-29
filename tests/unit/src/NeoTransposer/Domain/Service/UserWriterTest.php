<?php

namespace NeoTransposer\Tests\Domain\Service;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\Exception\BadUserRangeException;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Repository\BookRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\Service\UnhappinessManager;
use NeoTransposer\Domain\Service\UserWriter;
use NeoTransposer\Domain\ValueObject\NotesRange;

class UserWriterTest extends TestCase
{
    protected UserWriter $sut;

    protected $userRepository;

    protected $bookRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);

        $this->sut = new UserWriter(
            $this->userRepository,
            $this->bookRepository,
            $this->createMock(UnhappinessManager::class)
        );
    }

    public function test_write_user_sets_range_and_saves(): void
    {
        $user = new User('nobody@example.com', 1);

        $this->userRepository->expects($this->once())
            ->method('saveWithVoiceChange')
            ->with($user, User::METHOD_MANUAL);

        $this->sut->writeUser($user, null, 'E2', 'A3', null);

        $this->assertEquals(new NotesRange('E2', 'A3'), $user->range);
    }

    public function test_write_user_rejects_highest_note_in_octave_1(): void
    {
        $this->userRepository->expects($this->never())->method('saveWithVoiceChange');

        $this->expectException(BadUserRangeException::class);

        $this->sut->writeUser(new User('nobody@example.com', 1), null, 'C1', 'A1', null);
    }

    public function test_write_user_rejects_unknown_book(): void
    {
        $this->bookRepository->method('readAllBooks')->willReturn([1 => 'whatever']);

        $this->userRepository->expects($this->never())->method('saveWithVoiceChange');

        $this->expectException(BookNotExistException::class);

        $this->sut->writeUser(new User('nobody@example.com', 1), 99, null, null, null);
    }
}
