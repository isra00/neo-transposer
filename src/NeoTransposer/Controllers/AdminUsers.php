<?php

namespace NeoTransposer\Controllers;

class AdminUsers
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		$sql = <<<SQL
SELECT user.*, COUNT(worked) feedback
FROM user
LEFT JOIN transposition_feedback ON transposition_feedback.id_user = user.id_user
GROUP BY user.id_user
ORDER BY register_time DESC
SQL;
		$users = $app['db']->fetchAll($sql);

		$dbfile = $app['root_dir'] . '/' . $app['neoconfig']['mmdb'] . '.mmdb';
		$reader = new \GeoIp2\Database\Reader($dbfile);
	
		foreach ($users as &$user)
		{
			if (!empty($user['register_ip']))
			{
				try
				{
					$user['country'] = $reader->country($user['register_ip'])->country;
				}
				catch (Exception $e) {}
			}
		}

		$sql = <<<SQL
SELECT song.id_song, title
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song 
GROUP BY id_song
ORDER BY song.id_book, song.title
SQL;

		$fbsongs = $app['db']->fetchAll($sql);

		$feedback = array();

		foreach ($fbsongs as $song)
		{
			$feedback[$song['id_song']] = array(
				'yes' 	=> (int) $app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', array($song['id_song'])),
				'no'	=> (int) $app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', array($song['id_song'])),
				'title' => $song['title']
			);
			$feedback[$song['id_song']]['total'] = $feedback[$song['id_song']]['yes'] + $feedback[$song['id_song']]['no'];
		}

		$sql_gp_all = <<<SQL
SELECT worked, count(worked) n
FROM transposition_feedback
GROUP BY worked
WITH ROLLUP
SQL;

		$sql_gp_good_users = <<<SQL
SELECT worked, count(worked) n
FROM transposition_feedback
JOIN user on transposition_feedback.id_user = user.id_user AND CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1
GROUP BY worked
WITH ROLLUP
SQL;

		$global_performance['all'] = $app['db']->fetchAll($sql_gp_all);
		$global_performance['goods'] = $app['db']->fetchAll($sql_gp_good_users);

		$answers = array('no', 'yes');

		foreach ($global_performance as $group=>&$raw_data)
		{
			$feedback_data = array('yes'=>0, 'no'=>0, 'total'=>0);
			foreach ($raw_data as $row)
			{
				$key = is_null($row['worked']) ? 'total' : $answers[$row['worked']];
				$feedback_data[$key] = $row['n'];
			}

			$raw_data = $feedback_data;
		}

		$good_users = $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');

		return $app->render('admin_users.tpl', array(
			'users'					=> $users,
			'feedback'				=> $feedback,
			'global_performance'	=> $global_performance,
			'good_users'			=> $good_users,
		));
	}
}