<?php

namespace NeoTransposer\Tests\Infrastructure;

use Illuminate\Support\Facades\DB;
use NeoTransposer\Infrastructure\UnhappyUserRepositoryMysql;

class UnhappyUserRepositoryMysqlTest extends MysqlRepositoryTest
{
    protected UnhappyUserRepositoryMysql $unhappyUserRepositoryMysql;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('unhappy_user')->truncate();

        $this->unhappyUserRepositoryMysql = new UnhappyUserRepositoryMysql();
    }

    public function test_read_user_is_unhappy()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser]);
        $this->assertTrue($this->unhappyUserRepositoryMysql->readUserIsUnhappy($idUser));
    }

    public function test_read_user_is_unhappy_not_unhappy()
    {
        $this->assertFalse($this->unhappyUserRepositoryMysql->readUserIsUnhappy($this->faker->randomNumber()));
    }

    public function test_read_user_is_unhappy_and_no_action()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser, 'took_action' => null]);
        $this->assertTrue($this->unhappyUserRepositoryMysql->readUserIsUnhappyAndNoAction($idUser));
    }

    public function test_read_user_is_unhappy_and_no_action_with_action()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser, 'took_action' => $this->faker->dateTime()->format('Y-m-d H:i:s')]);
        $this->assertFalse($this->unhappyUserRepositoryMysql->readUserIsUnhappyAndNoAction($idUser));
    }

    public function test_read_user_is_unhappy_and_no_action_user_not_unhappy()
    {
        $this->assertFalse($this->unhappyUserRepositoryMysql->readUserIsUnhappyAndNoAction($this->faker->randomNumber()));
    }

    public function test_write_unhappy_user_previously_not_unhappy()
    {
        $idUser = $this->faker->randomNumber();
        $this->unhappyUserRepositoryMysql->writeUnhappyUser($idUser);
        $this->assertDatabaseHas('unhappy_user', ['id_user' => $idUser]);
    }

    public function test_write_unhappy_user_previously_unhappy()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser]);
        $this->unhappyUserRepositoryMysql->writeUnhappyUser($idUser);
        $this->assertDatabaseHas('unhappy_user', ['id_user' => $idUser]);
        // Should catch the UniqueConstraintViolationException and do nothing
    }

    public function test_delete_existing()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser]);
        $this->unhappyUserRepositoryMysql->delete($idUser);
        $this->assertDatabaseMissing('unhappy_user', ['id_user' => $idUser]);
    }

    public function test_delete_non_existing()
    {
        $this->unhappyUserRepositoryMysql->delete($this->faker->randomNumber());
    }

    public function test_update_unhappy_user_existing()
    {
        $idUser = $this->faker->randomNumber();
        DB::table('unhappy_user')->insert(['id_user' => $idUser]);
        $this->unhappyUserRepositoryMysql->updateUnhappyUser('std_male', 0.5, $idUser);
        $this->assertDatabaseHas('unhappy_user', ['id_user' => $idUser, 'action' => 'std_male', 'perf_before_action' => 0.5]);
    }

    public function test_update_unhappy_user_non_existing()
    {
        $idUser = $this->faker->randomNumber();
        $this->unhappyUserRepositoryMysql->updateUnhappyUser('std_male', 0.5, $idUser);
        $this->assertDatabaseMissing('unhappy_user', ['id_user' => $idUser, 'action' => 'std_male', 'perf_before_action' => 0.5]);
    }
}
