<?php

namespace NeoTransposer\Infrastructure;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

abstract class MysqlRepository
{
    protected Connection $dbConnection;

    public function __construct()
    {
        $this->dbConnection = DB::connection();
    }
}
