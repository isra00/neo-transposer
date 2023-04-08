<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;

class GetPerformanceByNumberOfFeedbacks implements AdminTask
{
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
    
	public function run(): string
	{
		$sql = <<<SQL
select fbs AS num_of_fbs, count(fbs) AS num_of_users, avg(performance) AS avg_perf
from (
 SELECT id_user, count(*) fbs, sum(worked) / count(*) as performance
 FROM `transposition_feedback`
 group by id_user
 order by fbs desc
) st
group by fbs
order by fbs desc
SQL;
		$data = $this->dbConnection->fetchAllAssociative($sql);
		$output = "# of FBs,# of users,AVG performance\n";
		foreach ($data as $row)
		{
			$output .= implode(',', $row) . "\n";
		}

		return $output;
	}
}