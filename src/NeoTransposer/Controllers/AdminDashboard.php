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
		//$this->populateCountry($app);
		$app['locale'] = 'es';
		
		$this->app = $app;

		$good_users = $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');

		$users_reporting_fb = $app['db']->fetchColumn('select count(distinct id_user) from transposition_feedback');

		return $app->render('admin_dashboard.twig', array(
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
			'perf_by_country'		=> $this->getPerformanceByCountry(),
		));

	}

	protected function populateCountry($app)
	{
		$ips = $app['db']->fetchAll('SELECT register_ip FROM user');

		$reader = new \GeoIp2\Database\Reader($app['root_dir'] . '/' . $app['neoconfig']['mmdb'] . '.mmdb');

		foreach ($ips as $ip)
		{
			$ip = $ip['register_ip'];

			if (!strlen(trim($ip)))
			{
				continue;
			}

			try
			{
				$record = $reader->country($ip);
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				continue;
			}

			$app['db']->update(
				'user',
				array('country' => $record->country->isoCode),
				array('register_ip' => $ip)
			);
		}
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
			@$feedback[$song['id_song']]['fb_shares'] = @json_decode(file_get_contents($graph_url), true)['shares'];
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
SELECT date(time) day
FROM transposition_feedback
GROUP BY day
ORDER BY day DESC
SQL;

		$days_with_feedback = $this->app['db']->fetchAll($sql);

		foreach ($days_with_feedback as $day)
		{
			$day = $day['day'];

			$sql = <<<SQL
SELECT '$day' day, 
	c_yes, c_no, c_yes+c_no c_total, c_yes/(c_yes+c_no)*100 c_performance,
	d_yes, d_no, d_yes+d_no d_total, d_yes/(d_yes+d_no)*100 d_performance
FROM
(
  SELECT date(time) day, count(worked) c_yes, worked
  FROM transposition_feedback
  WHERE date(time) <= '$day'
  AND worked=1
) sub_cyes
JOIN
(
  SELECT date(time) day, count(worked) c_no, worked
  FROM transposition_feedback
  WHERE date(time) <= '$day'
  AND worked=0
) sub_cno
join
(
  SELECT date(time) day, count(worked) d_yes, worked
  FROM transposition_feedback
  WHERE date(time) = '$day'
  AND worked=1
) sub_dyes
join
(
  SELECT date(time) day, count(worked) d_no, worked
  FROM transposition_feedback
  WHERE date(time) = '$day'
  AND worked=0
) sub_dno
SQL;

			$global_perf_chrono[] = $this->app['db']->fetchAll($sql)[0];

		}

		return $global_perf_chrono;
	}

	protected function getSongsWithFeedback()
	{
		$sql = <<<SQL
SELECT nofb.id_book, nofb.nofb, total.total FROM
(
 SELECT id_book, count(song.id_song) nofb
 FROM song
 LEFT JOIN transposition_feedback ON transposition_feedback.id_song = song.id_song
 WHERE transposition_feedback.id_song IS NULL
 GROUP BY id_book
) nofb
JOIN
(
	SELECT id_book, count(id_song) total FROM song GROUP BY id_book
) total ON nofb.id_book = total.id_book
SQL;
		return $this->app['db']->fetchAll($sql);
	}

	protected function getMostActiveUsers()
	{
		$sql = <<<SQL
SELECT user.id_user, user.email, user.lowest_note, user.highest_note, y.yes yes, n.no no, y.yes + n.no total, yes/(y.yes + n.no) perf
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
ORDER BY total DESC
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

	protected function getPerformanceByCountry()
	{
		$sql = <<<SQL
select country, count(user.id_user) n
from user
join transposition_feedback on transposition_feedback.id_user = user.id_user
where not country is null
group by country
order by n desc
SQL;
		$countries = $this->app['db']->fetchAll($sql);

		$performance = array();

		foreach ($countries as $country)
		{
			$country = $country['country'];

			$sql = <<<SQL
select '$country' country, yes, no, yes+no total, yes/(yes+no)*100 performance
from
(
  SELECT date(time) day, count(worked) yes, worked
  FROM transposition_feedback
  join user on transposition_feedback.id_user = user.id_user
  where user.country = '$country'
  and worked=1
) sub_yes
join
(
  SELECT date(time) day, count(worked) no, worked
  FROM transposition_feedback
  join user on transposition_feedback.id_user = user.id_user
  where user.country = '$country'
  and worked=0
) sub_no
SQL;
			$countryPerformance = $this->app['db']->fetchAll($sql);
			$performance[$country] = $countryPerformance[0];
		}

		usort($performance, function($a, $b) 
		{
			return ($a['performance'] < $b['performance']) ? 1 : -1;
		});

		return $performance;
	}
}