<?php

namespace NeoTransposer\Infrastructure;

use Doctrine\DBAL\Connection;

abstract class MysqlRepository
{
    /**
     * @var Connection
     */
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
}
