<?php

namespace NeoTransposer\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\DB;

abstract class MysqlRepository
{
    protected \Illuminate\Database\Connection $dbConnection;

    protected static $dbal;

    public function __construct()
    {
        $this->dbConnection = DB::connection();
    }

    public static function dbal(): Connection
    {
        if (is_null(self::$dbal)) {
            self::$dbal = DriverManager::getConnection([
                'driver'   => 'pdo_mysql',
                'host'     => config('database.connections.mysql.host'),
                'user'     => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'dbname'   => config('database.connections.mysql.database'),
                'charset'  => 'utf8',
            ]);
        }
        return self::$dbal;
    }
}
