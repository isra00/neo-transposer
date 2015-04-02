<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminUsers
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		$sql = <<<SQL
SELECT user.*, y.yes yes, n.no no, y.yes + n.no total
FROM user
LEFT JOIN
(
	SELECT id_user, COUNT(worked) yes
	FROM transposition_feedback
	WHERE worked=1
	GROUP BY id_user
) y ON user.id_user = y.id_user
LEFT JOIN
(
	SELECT id_user, COUNT(worked) no
	FROM transposition_feedback
	WHERE worked=0
	GROUP BY id_user
) n ON y.id_user = n.id_user
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
				catch (\Exception $e) {}
			}
		}

		$sql = <<<SQL
SELECT song.id_song, title, song.slug
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
			
			$song_url = $app['url_generator']->generate('transpose_song', array('id_song' => $song['slug']), UrlGeneratorInterface::ABSOLUTE_URL);
			$graph_url = 'http://graph.facebook.com/' . $song_url;
			$feedback[$song['id_song']]['fb_shares'] = @json_decode(file_get_contents($graph_url), true)['shares'];
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

		$sql = <<<SQL
SELECT id_song, user.id_user, worked, time, email
FROM `transposition_feedback`
join user on transposition_feedback.id_user = user.id_user
WHERE user.lowest_note IS NULL
ORDER BY time DESC
SQL;
		$null_users_with_feedback = $app['db']->fetchAll($sql);

		$sql = <<<SQL
select date(time) day
from transposition_feedback
group by day
order by day asc
SQL;

		$days_with_feedback = $app['db']->fetchAll($sql);

		foreach ($days_with_feedback as $day)
		{
			$day = $day['day'];

			$sql = <<<SQL
select sub_yes.day, yes, no, yes+no total, yes/(yes+no)*100 performance
from
(
  SELECT date(time) day, count(worked) yes, worked
  FROM transposition_feedback
  where date(time) <= '$day'
  and worked=1
) sub_yes
join
(
  SELECT date(time) day, count(worked) no, worked
  FROM transposition_feedback
  where date(time) <= '$day'
  and worked=0
) sub_no
SQL;

			$global_perf_chrono[] = $app['db']->fetchAll($sql)[0];

		}

		return $app->render('admin_users.tpl', array(
			'users'					=> $users,
			'feedback'				=> $feedback,
			'global_performance'	=> $global_performance,
			'global_perf_chrono'	=> $global_perf_chrono,
			'good_users'			=> $good_users,
			'null_users_with_fb'	=> $null_users_with_feedback,
		));
	}
}