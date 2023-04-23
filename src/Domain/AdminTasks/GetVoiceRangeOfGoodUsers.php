<?php

namespace App\Domain\AdminTasks;

use Doctrine\DBAL\Connection;
use App\Domain\NotesCalculator;

final class GetVoiceRangeOfGoodUsers implements AdminTask
{
    public function __construct(protected Connection $dbConnection)
    {
    }
    
	public function run(): string
	{
		$goodUsers = $this->dbConnection->fetchAllAssociative('SELECT id_user, wizard_step1, lowest_note, highest_note FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
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