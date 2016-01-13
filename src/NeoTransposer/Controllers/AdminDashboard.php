<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
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

	public function get(Request $req, \NeoTransposer\NeoApp $app)
	{
		$app['locale'] = 'es';
		
		$this->app = $app;

		$user_count = $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user');
		$good_users = $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
		$users_reporting_fb = $app['db']->fetchColumn('select count(distinct id_user) from transposition_feedback');

		$toolOutput = '';

		if ($tool = $req->get('tool'))
		{
			$tools = new \NeoTransposer\Model\AdminTools;

			switch ($tool)
			{
				case 'populateCountry':
					$toolOutput = $tools->populateCountry($app);
					break;

				case 'checkLowerHigherNotes':
					$toolOutput = $tools->checkLowerHigherNotes($app);
					break;

				case 'refreshCss':
					$toolOutput = $tools->refreshCss($app);
					break;

				case 'checkChordOrder':
					$toolOutput = $tools->checkChordOrder($app);
					$toolOutput = empty($toolOutput)
						? 'NO inconsistences found :-)'
						: 'Songs with problems: ' . implode(', ', $toolOutput);
					break;
			}
		}

		return $app->render('admin_dashboard.twig', array(
			'user_count'			=> $user_count,
			'good_users'			=> $good_users,
			'song_availability'		=> $this->getSongAvailability(),
			'global_performance'	=> $this->getGlobalPerformance(),
			'users_reporting_fb'	=> $users_reporting_fb,
			'unhappy_users'			=> $this->getUnhappyUsers(),
			'songs_with_fb'			=> $this->getSongsWithFeedback(),
			'most_active_users'		=> $this->getMostActiveUsers(),
			'perf_by_country'		=> $this->getPerformanceByCountry(),
			'global_perf_chrono'	=> $req->get('long') ? $this->fetchGlobalPerfChrono() : null,
			'feedback'				=> $req->get('long') ? $this->getFeedback() : null,
			'good_users_chrono'		=> $req->get('long') ? $this->getGoodUsersChrono() : null,
			'tool_output'			=> $toolOutput,
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

	protected function getSongAvailability()
	{
		$sql = <<<SQL
SELECT 
  book.id_book, 
  song_count total,
  sc.current
FROM 
  book
JOIN
(
  SELECT id_book, count(id_song) current FROM song GROUP BY id_book
) sc ON sc.id_book = book.id_book
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	protected function getFeedback()
	{
		$nc = new \NeoTransposer\Model\NotesCalculator;

		$sql = <<<SQL
SELECT song.id_song, title, song.slug, song.lowest_note, song.highest_note
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song 
GROUP BY id_song
ORDER BY song.id_book, song.title
SQL;

		$fbsongs = $this->app['db']->fetchAll($sql);

		$feedback = array();

		foreach ($fbsongs as $song)
		{
			$yes = (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', array($song['id_song']));
			$no = (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', array($song['id_song']));

			$feedback[$song['id_song']] = array(
				'yes'			=> $yes,
				'no'			=> $no,
				'performance'	=> $yes / ($yes + $no),
				
				'title'			=> $song['title'],
				'lowest_note'	=> $song['lowest_note'],
				'highest_note'	=> $song['highest_note'],
				'wideness'		=> $nc->distanceWithOctave($song['highest_note'], $song['lowest_note']),
			);
			$feedback[$song['id_song']]['total'] = $feedback[$song['id_song']]['yes'] + $feedback[$song['id_song']]['no'];
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
SELECT user.*, y.yes yes, n.no no, y.yes + n.no total, yes/(y.yes + n.no) perf
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

	protected function getCountryNamesList()
	{
		$dbfile = $this->app['root_dir'] . '/' . $this->app['neoconfig']['mmdb'] . '.mmdb';
		$reader = new \GeoIp2\Database\Reader($dbfile);

		$ips_for_country = $this->app['db']->fetchAll('SELECT country, register_ip FROM user WHERE NOT country IS NULL GROUP BY country');
		$country_names = array();

		foreach ($ips_for_country as $ip)
		{
			try {
				$country_names[$ip['country']] = $reader->country($ip['register_ip'])->country->names['en'];
			}
			catch (\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$country_names[$ip['country']] = $ip['country'];
			}
		}

		return $country_names;
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

		$country_names = $this->getCountryNamesList();

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

			if ($countryPerformance[0]['total'] > 5)
			{
				$performance[$country] = $countryPerformance[0];
				$performance[$country]['country_name'] = $country_names[$country];
			}
		}

		usort($performance, function($a, $b) 
		{
			return ($a['performance'] < $b['performance']) ? 1 : -1;
		});

		return $performance;
	}
}