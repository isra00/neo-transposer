<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use NeoTransposer\Model\NotesCalculator;

class GetVoiceRangeOfGoodUsers implements AdminTask
{
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
    
	public function run(): string
	{
		$goodUsers = $this->dbConnection->fetchAll('SELECT id_user, wizard_step1, lowest_note, highest_note FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
		$output = '';

		$nc = new NotesCalculator();

		foreach ($goodUsers as $user)
		{
			$output .= $user['id_user'] . ',' . $user['wizard_step1'] . ',' . $user['lowest_note'] . ","
			 . array_search($user['lowest_note'], $nc->numbered_scale) . ","
			 . $user['highest_note'] . ","
			 . array_search($user['highest_note'], $nc->numbered_scale) . "\n";
		}

		return $output;
	}
}