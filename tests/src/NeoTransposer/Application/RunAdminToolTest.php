<?php

namespace NeoTransposer\Tests\Application;

use NeoTransposer\Application\AdminTaskNotExistException;
use NeoTransposer\Application\RunAdminTool;
use NeoTransposer\Domain\AdminTasks\AdminTask;
use PHPUnit\Framework\TestCase;
use Silex\Application;

class RunAdminToolTest extends TestCase
{
    protected function getDC(array $entries = []): Application
    {
        return new Application($entries);
    }

    public function testShouldThrowExceptionWhenTaskNameIsInvalid()
    {
        $sut = new RunAdminTool($this->getDC());
        $this->expectException(AdminTaskNotExistException::class);
        $this->expectExceptionMessage("Invalid task name invalid");
        $sut->runAdminTask("invalid");
    }

    public function testShouldThrowExceptionWhenDCDoesNotHaveTask()
    {
        $sut = new RunAdminTool($this->getDC());
        $this->expectException(AdminTaskNotExistException::class);
        $this->expectExceptionMessage("Dependency container didn't find valid task name PopulateUsersCountry");
        $sut->runAdminTask("PopulateUsersCountry");
    }

    public function testTaskRan()
    {
        $mockTask = $this->createMock(AdminTask::class);
        $mockTask->expects($this->once())
            ->method('run')
            ->willReturn('I ran');

        $dcMock = $this->getDC(["NeoTransposer\\Domain\\AdminTasks\\PopulateUsersCountry" => $mockTask]);
        $sut = new RunAdminTool($dcMock);

        $this->assertEquals('I ran', $sut->runAdminTask("PopulateUsersCountry"));
    }
}
