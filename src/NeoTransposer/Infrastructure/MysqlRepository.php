<?php

namespace NeoTransposer\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\DB;

abstract class MysqlRepository
{
    protected \Illuminate\Database\Connection $dbConnection;

    public function __construct()
    {
        $this->dbConnection = DB::connection();
    }
}
