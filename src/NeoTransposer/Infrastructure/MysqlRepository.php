<?php

namespace NeoTransposer\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

abstract class MysqlRepository
{
    public function __construct(protected Connection $dbConnection, protected EntityManager $entityManager)
    {
    }
}