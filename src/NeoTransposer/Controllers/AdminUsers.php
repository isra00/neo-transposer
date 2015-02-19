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

		$fbsongs = $app['db']->fetchAll('SELECT song.id_song, title FROM transposition_feedback JOIN song ON transposition_feedback.id_song = song.id_song GROUP BY id_song');

		$feedback = array();

		foreach ($fbsongs as $song)
		{
			$feedback[$song['id_song']] = array(
				'yes' 	=> (int) $app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', array($song['id_song'])),
				'no'	=> (int) $app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', array($song['id_song'])),
				'title' => $song['title']
			);
		}

//		var_dump($feedback);

		return $app->render('admin_users.tpl', array(
			'users' => $users,
			'feedback' => $feedback
		));
	}
}