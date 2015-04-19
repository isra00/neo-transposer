<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Administrator's dashboard.
 */
class AdminDashboard
{
	/**
	 * The App in use, for easier access.
	 * @var \NeoTransposer\NeoApp
	 */
	protected $app;

	public function get(\NeoTransposer\NeoApp $app)
	{
		$app['locale'] = 'es';
		
		$this->app = $app;

		$good_users = $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');

		$users_reporting_fb = $app['db']->fetchColumn('select count(distinct id_user) from transposition_feedback');

		return $app->render('admin_users.twig', array(
			'good_users'			=> $good_users,
			'global_performance'	=> $this->getGlobalPerformance(),
			'users_reporting_fb'	=> $users_reporting_fb,
			'global_perf_chrono'	=> $this->fetchGlobalPerfChrono(),
			'feedback'				=> $this->getFeedback(),
			'unhappy_users'			=> $this->getUnhappyUsers(),
			'users'					=> $this->getUsers(),
			'songs_with_fb'			=> $this->getSongsWithFeedback(),
			'most_active_users'		=> $this->getMostActiveUsers(),
			'good_users_chrono'		=> $this->getGoodUsersChrono(),
		));
	}

	protected function getGlobalPerformance()
	{
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

		$global_performance['all'] = $this->app['db']->fetchAll($sql_gp_all);
		$global_performance['goods'] = $this->app['db']->fetchAll($sql_gp_good_users);

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

		return $global_performance;
	}

	protected function getFeedback()
	{
		$sql = <<<SQL
SELECT song.id_song, title, song.slug
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song 
GROUP BY id_song
ORDER BY song.id_book, song.title
SQL;

		$fbsongs = $this->app['db']->fetchAll($sql);

		$feedback = array();

		foreach ($fbsongs as $song)
		{
			$feedback[$song['id_song']] = array(
				'yes' 	=> (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', array($song['id_song'])),
				'no'	=> (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', array($song['id_song'])),
				'title' => $song['title']
			);
			$feedback[$song['id_song']]['total'] = $feedback[$song['id_song']]['yes'] + $feedback[$song['id_song']]['no'];
			
			$song_url = $this->app['url_generator']->generate('transpose_song', array('id_song' => $song['slug']), UrlGeneratorInterface::ABSOLUTE_URL);
			$graph_url = 'http://graph.facebook.com/' . $song_url;
			//@$feedback[$song['id_song']]['fb_shares'] = @json_decode(file_get_contents($graph_url), true)['shares'];
		}

		return $feedback;
	}

	protected function getUnhappyUsers()
	{
		$sql = <<<SQL
SELECT user.id_user, user.email, y.yes yes, n.no no, y.yes + n.no total, yes/(y.yes + n.no) perf
FROM user
JOIN
(
	SELECT id_user, COUNT(worked) yes
	FROM transposition_feedback
	WHERE worked=1
	GROUP BY id_user
) y ON user.id_user = y.id_user
JOIN
(
	SELECT id_user, COUNT(worked) no
	FROM transposition_feedback
	WHERE worked=0
	GROUP BY id_user
) n ON y.id_user = n.id_user
where yes/(y.yes + n.no) < 0.5
ORDER BY total DESC
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	protected function getUsers()
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
		$users = $this->app['db']->fetchAll($sql);

		$dbfile = $this->app['root_dir'] . '/' . $this->app['neoconfig']['mmdb'] . '.mmdb';
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

		return $users;
	}

	protected function fetchGlobalPerfChrono()
	{
		$sql = <<<SQL
select date(time) day
from transposition_feedback
group by day
order by day asc
SQL;

		$days_with_feedback = $this->app['db']->fetchAll($sql);

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

			$global_perf_chrono[] = $this->app['db']->fetchAll($sql)[0];

		}

		return $global_perf_chrono;
	}

	protected function getSongsWithFeedback()
	{
		$sql = <<<SQL
select nofb.id_book, nofb.nofb, total.total from
(
 select id_book, count(song.id_song) nofb
 from song
 left join transposition_feedback on transposition_feedback.id_song = song.id_song
 where transposition_feedback.id_song is null
 group by id_book
) nofb
join
(select id_book, count(id_song) total from song group by id_book) total
on nofb.id_book = total.id_book
SQL;
		return $this->app['db']->fetchAll($sql);
	}

	protected function getMostActiveUsers()
	{
		$sql = <<<SQL
SELECT user.id_user, user.email, user.highest_note, user.lowest_note, count(transposition_feedback.id_song) fb
FROM user
join transposition_feedback on transposition_feedback.id_user = user.id_user
GROUP BY transposition_feedback.id_user
ORDER BY fb DESC
LIMIT 20
SQL;
		return $this->app['db']->fetchAll($sql);
	}

	protected function getGoodUsersChrono()
	{
		$sql = <<<SQL
SELECT date(register_time) day, goods.n goods, COUNT(id_user) total, concat(round((goods.n/COUNT(id_user))*100), '%') goods_rate
FROM user
join
(
  select date(register_time) dayg, count(id_user) n
  from user
  WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1
  group by dayg
) goods on date(user.register_time) = goods.dayg
group by day
order by day desc
SQL;
		return $this->app['db']->fetchAll($sql);
	}
}