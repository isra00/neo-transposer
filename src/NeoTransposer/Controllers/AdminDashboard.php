<?php

namespace NeoTransposer\Controllers;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\UnhappyUser;

/**
 * Administrator's dashboard.
 */
class AdminDashboard
{
	const DETAILED_FB_DEPLOYED = '2017-08-11';

	/**
	 * The App in use, for easier access.
	 * @var \NeoTransposer\NeoApp
	 */
	protected $app;

    /**
     * @var array
     */
    protected $countryNames;

	public function get(Request $req, \NeoTransposer\NeoApp $app): string
	{
		$app['locale'] 	= 'es';
		$this->app 		= $app;

		$user_count 	= $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user');
		$good_users 	= $app['db']->fetchColumn('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');

		$toolOutput 	= '';

		if ($tool = $req->get('tool'))
		{
			$toolsMethods = [
				'populateCountry', 
				'checkLowerHigherNotes', 
				'refreshCss',
				'removeOldCompiledCss',
				'testAllTranspositions',
				'getVoiceRangeOfGoodUsers',
				'detectOrphanChords',
				'checkChordOrder',
				'checkUserLowerHigherNotes',
				'getPerformanceByNumberOfFeedbacks',
				'diffTranslations'
			];

			if (false === array_search($tool, $toolsMethods))
			{
				$app->abort(404);
			}

			$tools 		= new \NeoTransposer\Model\AdminTools($app);
			$toolOutput = $tools->{$tool}();
		}

		return $app->render('admin_dashboard.twig', array(
			'page_title'			=> 'Dashboard · ' . $app['neoconfig']['software_name'],
			'user_count'			=> $user_count,
			'good_users'			=> $good_users,
			'song_availability'		=> $this->getSongAvailability(),
			'global_performance'	=> $this->getGlobalPerformance(),
			'users_reporting_fb'	=> $this->getUsersReportingFeedback(),
			'unhappy_users'			=> $this->getUnhappyUsers(),
			'songs_with_fb'			=> $this->getSongsWithFeedback(),
			'most_active_users'		=> $req->get('long') ? $this->getMostActiveUsers() : [],
			'perf_by_country'		=> $this->getPerformanceByCountry(),
			'global_perf_chrono'	=> $req->get('long') ? $this->fetchGlobalPerfChrono() : null,
			'feedback'				=> $req->get('long') ? $this->getFeedback() : [],
			'good_users_chrono'		=> $req->get('long') ? $this->getGoodUsersChrono() : null,
			'tool_output'			=> $toolOutput,
			'countries'				=> $this->getCountryNamesList(),
			'dfb_transposition'		=> $this->getDFBTransposition(),
			'dfb_pc_status'			=> $this->getDFBPcStatus(),
			'dfb_centered_scorerate'=> $this->getDFBCenteredScoreRate(),
			'dfb_deviation'			=> $this->getDFBDeviation(),
			'usersByBook'			=> $this->getUsersByBook(intval($user_count)),
			'performanceByBook'		=> $this->getPerformanceByBook(),
			'performanceByVoice'	=> $this->getPerformanceByVoice(),
		), false);
	}

	protected function getGlobalPerformance(): array
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

		foreach ($global_performance as &$raw_data)
		{
			$raw_data = $this->aggregatePerformanceData($raw_data);
		}

		return $global_performance;
	}

	protected function getUsersReportingFeedback()
	{
		$sqlUsersReportingFb = <<<SQL
SELECT COUNT(distinct id_user) AS users_reporting_fb, good_users, not_null_users
FROM transposition_feedback,
(
	SELECT COUNT(DISTINCT id_user) AS good_users
	FROM user
	WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1
) good_users
,
(
	SELECT COUNT(DISTINCT id_user) AS not_null_users
	FROM user
	WHERE NOT user.lowest_note IS NULL
) not_null_users
SQL;
		$this->app['db']->executeQuery("SET sql_mode=''");
		return $this->app['db']->fetchAssoc($sqlUsersReportingFb);
	}

	/**
	 * @return (int|mixed)[]
	 *
	 * @psalm-return array{yes: int, no: int, total: int}
	 */
	protected function aggregatePerformanceData(array $raw_data): array
	{
		$answers = array('no', 'yes');

		$feedback_data = array('yes'=>0, 'no'=>0, 'total'=>0);
		foreach ($raw_data as &$row)
		{
			$key = is_null($row['worked']) ? 'total' : $answers[$row['worked']];
			$feedback_data[$key] = $row['n'];
		}

		return $feedback_data;
	}

	protected function getSongAvailability()
	{
		$sql = <<<SQL
SELECT 
  book.id_book, 
  song_count total,
  sc.current,
  peopledata.peopledata
FROM 
  book
JOIN
(
  SELECT id_book, COUNT(id_song) current FROM song GROUP BY id_book
) sc ON sc.id_book = book.id_book
JOIN
(
  SELECT id_book, COUNT(id_song) peopledata FROM song WHERE NOT people_lowest_note = '' AND NOT people_lowest_note IS NULL GROUP BY id_book
) peopledata ON peopledata.id_book = book.id_book
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	/**
	 * @return (float|int|mixed)[][]
	 *
	 * @psalm-return array<array{yes: int, no: int, performance: float, title: mixed, lowest_note: mixed, highest_note: mixed, wideness: int, total?: int}>
	 */
	protected function getFeedback(): array
	{
		$nc = new \NeoTransposer\Model\NotesCalculator;

		$sql = <<<SQL
SELECT song.id_song, title, song.lowest_note, song.highest_note, count(*) fbs
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song 
GROUP BY id_song
ORDER BY song.id_book, fbs DESC
SQL;

		$fbsongs = $this->app['db']->fetchAll($sql);

		$feedback = [];

		foreach ($fbsongs as $song)
		{
			$yes = (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', array($song['id_song']));
			$no  = (int) $this->app['db']->fetchColumn('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', array($song['id_song']));

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
SELECT unhappy_user.*, user.id_user id_user, user.email, y.yes yes, n.no no, y.yes + n.no total, yes/(y.yes + n.no) perf
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
) n ON user.id_user = n.id_user
LEFT JOIN unhappy_user ON n.id_user = unhappy_user.id_user
WHERE 
(
	unhappy_user.id_user IS NULL
	AND yes/(y.yes + n.no) < ?
	AND (y.yes + n.no) >= ?
)
OR
(
	NOT unhappy_user.id_user IS NULL
)
ORDER BY took_action, time_unhappy, total DESC
SQL;

		return $this->app['db']->fetchAll($sql,[
			UnhappyUser::UNHAPPY_THRESHOLD_PERF, 
			UnhappyUser::UNHAPPY_THRESHOLD_REPORTS
		]);
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
/* Trick for ES & PT book (the JOIN on NULL fails and does not appear, since 
 * they have 100%), unnecessary in Mysql >= 8 
 */
UNION
SELECT id_book, 0, COUNT(DISTINCT id_song)
FROM transposition_feedback
JOIN song USING (id_song)
GROUP BY id_book
HAVING id_book=2 OR id_book=4
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
LIMIT 30
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

	/**
	 * Get the names of all countries in user.country by geo-locating any IP for
	 * each country
	 */
	protected function getCountryNamesList(): array
	{
		if (!empty($this->countryNames))
		{
			return $this->countryNames;
		}

		$reader = $this->app['geoIp2Reader'];

		//ONLY_FULL_GROUP_BY mode (default in MySQL>5.7) makes the query fail
		$this->app['db']->query("SET @@sql_mode=''");
		$ips_for_country = $this->app['db']->fetchAll('SELECT country, register_ip FROM user WHERE NOT country IS NULL GROUP BY country');
		$country_names = [];

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

		$this->countryNames = $country_names;
		return $country_names;
	}

	/**
	 * @psalm-return list<mixed>
	 */
	protected function getPerformanceByCountry(): array
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

		$performance = [];

		$country_names = $this->getCountryNamesList();

		$sql = <<<SQL
SELECT user.country, COUNT(id_user) total, good
FROM 
  user
JOIN
  (
    SELECT country, COUNT(id_user) good 
    FROM user 
    WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1
    GROUP BY user.country
  ) goods ON goods.country = user.country
GROUP BY user.country order by total desc
SQL;

		$goodUsersCountryRaw = $this->app['db']->fetchAll($sql);

		$goodUsersCountry = [];
		foreach ($goodUsersCountryRaw as $row)
		{
			$goodUsersCountry[$row['country']] = $row['good'] / $row['total'];
		}

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
				$performance[$country]['country_name'] 	= $country_names[$country];
				$performance[$country]['good_users']	= $goodUsersCountry[$country];
			}
		}

		usort($performance, function($a, $b) 
		{
			return ($a['performance'] < $b['performance']) ? 1 : -1;
		});

		return $performance;
	}

	protected function getDFBTransposition()
	{
		$sql = <<<SQL
SELECT transposition, count(*) fbs
FROM transposition_feedback
WHERE worked = 1
AND time > ?
GROUP BY transposition
ORDER BY fbs DESC
SQL;
		$fbsByTransposition = $this->app['db']->fetchAll($sql, [self::DETAILED_FB_DEPLOYED]);

		$total = array_sum(array_column($fbsByTransposition, 'fbs'));
		foreach ($fbsByTransposition as &$fbs)
		{
			$fbs['fbs_relative'] = $fbs['fbs'] / $total;
		}

		return $fbsByTransposition;
	}

	protected function getDFBPcStatus()
	{
		$sql = <<<SQL
SELECT pc_status, SUM(fbs) fbss, SUM(Case When transposition = 'peopleCompatible' THEN fbs ELSE 0 End) chosePeopleCompatible
FROM
(
  SELECT pc_status, transposition, count(*) fbs
  FROM `transposition_feedback`
  where not pc_status is null
  group by pc_status, transposition
) sub
GROUP BY pc_status
ORDER BY pc_status;
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	protected function getDFBCenteredScoreRate()
	{
		$sql = <<<SQL
SELECT id_song, title, time, centered_score_rate
FROM transposition_feedback
JOIN song USING (id_song)
WHERE transposition = 'centered2'
AND NOT centered_score_rate IS NULL
ORDER BY song.id_book, centered_score_rate DESC
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	protected function getDFBDeviation()
	{
		$sql = <<<SQL
SELECT transposition, deviation_from_center, count(*) fbs
FROM transposition_feedback
WHERE NOT deviation_from_center IS NULL
GROUP BY transposition, deviation_from_center
ORDER BY deviation_from_center
SQL;

		return $this->app['db']->fetchAll($sql);
	}

	protected function getUsersByBook(int $totalUsers): array
	{
		$sql = <<<SQL
SELECT lang_name, id_book, count(id_user) users
FROM user
LEFT JOIN book USING (id_book)
GROUP BY user.id_book 
ORDER BY users DESC
SQL;

		$users = $this->app['db']->fetchAll($sql);
		$usersBookId = [];

		foreach ($users as &$book)
		{
			$book['percent'] = (intval($book['users']) / $totalUsers) * 100;

			if (empty($book['lang_name']))
			{
				$book['lang_name'] = '(not set)';
			}

			$usersBookId[$book['id_book']] = $book;
		}

		return $usersBookId;
	}

	protected function getPerformanceByBook(): array
	{
		$sql = <<<SQL
SELECT worked, count(worked) n
FROM transposition_feedback
JOIN song USING (id_song)
WHERE id_book = ?
GROUP BY worked
WITH ROLLUP
SQL;

		$performance = [];

		foreach ($this->app['books'] as $book)
		{
			$performance[$book['id_book']] = $this->aggregatePerformanceData(
				$this->app['db']->fetchAll($sql, [$book['id_book']])
			);
		}

		return $performance;
	}

	protected function getPerformanceByVoice()
	{
		$sql = <<<SQL
SELECT user.wizard_step1 AS voiceType, count(*) AS fbs, sum(worked) / count(*) AS performance
FROM `transposition_feedback`
JOIN user USING (id_user)
GROUP BY user.wizard_step1
SQL;
		return $this->app['db']->fetchAll($sql);
	}
}
