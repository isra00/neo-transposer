<?php

namespace NeoTransposer\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

abstract class MysqlRepository
{
    protected Connection $dbConnection;
    protected EntityManager $entityManager;

    public function __construct(Connection $dbConnection, EntityManager $entityManager)
    {
        $this->dbConnection = $dbConnection;
        $this->entityManager = $entityManager;
    }
}