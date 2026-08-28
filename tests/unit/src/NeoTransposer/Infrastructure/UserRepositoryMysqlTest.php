<?php

namespace NeoTransposer\Tests\Infrastructure;

use Illuminate\Support\Facades\DB;
use NeoTransposer\Infrastructure\FeedbackRepositoryMysql;
use NeoTransposer\Infrastructure\UserRepositoryMysql;

class UserRepositoryMysqlTest extends MysqlRepositoryTest
{
    protected UserRepositoryMysql $userRepositoryMysql;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user')->truncate();

        $this->userRepositoryMysql = new UserRepositoryMysql(new FeedbackRepositoryMysql());
    }

    public function test_read_from_email_finds_the_user()
    {
        $email = $this->faker->unique()->safeEmail();
        DB::table('user')->insert(['email' => $email]);

        $this->assertSame($email, $this->userRepositoryMysql->readFromEmail($email)?->email);
    }

    /**
     * Users type their e-mail with arbitrary capitalisation, so the lookup must not
     * care. This relies on the column's case-insensitive collation (utf8mb3_general_ci),
     * which is easy to lose in a migration -- hence the test.
     *
     * Codeception 5 resolves data providers from this annotation only; the PHPUnit 11
     * #[DataProvider] attribute is silently ignored, which makes the test run with no
     * arguments and error out.
     *
     * @dataProvider provideCasings
     */
    public function test_read_from_email_is_case_insensitive(string $typed)
    {
        DB::table('user')->insert(['email' => 'Mixed.Case@Example.com']);

        $this->assertNotNull($this->userRepositoryMysql->readFromEmail($typed));
    }

    public static function provideCasings(): array
    {
        return [
            'as stored'  => ['Mixed.Case@Example.com'],
            'lowercase'  => ['mixed.case@example.com'],
            'UPPERCASE'  => ['MIXED.CASE@EXAMPLE.COM'],
            'other case' => ['mIXED.cASE@eXAMPLE.COM'],
        ];
    }

    public function test_read_from_email_non_existing()
    {
        DB::table('user')->insert(['email' => 'someone@gmail.com']);

        $this->assertNull($this->userRepositoryMysql->readFromEmail('nobody@gmail.com'));
    }

    /**
     * Login is passwordless, so matching an e-mail *is* authentication: a lookup that
     * honoured SQL LIKE wildcards let anyone log in as an arbitrary user (audit C1).
     *
     * @dataProvider provideLikeWildcards
     */
    public function test_read_from_email_does_not_honour_like_wildcards(string $wildcardEmail)
    {
        DB::table('user')->insert(['email' => 'victim@gmail.com']);

        $this->assertNull($this->userRepositoryMysql->readFromEmail($wildcardEmail));
    }

    public static function provideLikeWildcards(): array
    {
        return [
            'percent matches any sequence'  => ['%@gmail.com'],
            'underscore matches any char'   => ['_ictim@gmail.com'],
            'percent alone matches all'     => ['%'],
            'percent in the local part'     => ['vic%@gmail.com'],
        ];
    }

    /**
     * The id_user branch of readFromField() shares the same query.
     */
    public function test_read_from_id()
    {
        DB::table('user')->insert(['id_user' => 4321, 'email' => 'victim@gmail.com']);

        $this->assertEquals(4321, $this->userRepositoryMysql->readFromId(4321)?->id_user);
        $this->assertNull($this->userRepositoryMysql->readFromId(4322));
    }
}
