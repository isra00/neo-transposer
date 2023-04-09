<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use NeoTransposer\Domain\Service\UnhappinessManager;

class AdminMetricsRepositoryMysql extends MysqlRepository implements AdminMetricsRepository
{
    protected $countryNames;

    public function readUserCountTotal(): int
    {
        return intval($this->dbConnection->fetchOne('SELECT COUNT(id_user) FROM user'));
    }

    public function readUserCountGood(): int
    {
        return intval($this->dbConnection->fetchOne('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1'));
    }

	public function readGlobalPerformance(): array
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

        $global_performance = [
            'all'   => $this->dbConnection->fetchAllAssociative($sql_gp_all),
            'goods' => $this->dbConnection->fetchAllAssociative($sql_gp_good_users)
        ];

		$answers = ['no', 'yes'];

		foreach ($global_performance as &$raw_data)
		{
			$raw_data = $this->aggregatePerformanceData($raw_data);
		}

		return $global_performance;
	}

	public function readUsersReportingFeedback(): array
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
		$this->dbConnection->executeQuery("SET sql_mode=''");
		return $this->dbConnection->fetchAssociative($sqlUsersReportingFb);
	}

	/**
	 * @return (int|mixed)[]
	 *
	 * @psalm-return array{yes: int, no: int, total: int}
	 */
	protected function aggregatePerformanceData(array $raw_data): array
	{
		$answers = ['no', 'yes'];

		$feedback_data = ['yes'=>0, 'no'=>0, 'total'=>0];
		foreach ($raw_data as &$row)
		{
			$key = is_null($row['worked']) ? 'total' : $answers[$row['worked']];
			$feedback_data[$key] = $row['n'];
		}

		return $feedback_data;
	}

	public function readSongAvailability(): array
    {
		$sql = <<<SQL
SELECT
  book.lang_name,
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

		return $this->dbConnection->fetchAllAssociative($sql);
	}

	/**
	 * @return (float|int|mixed)[][]
	 *
	 * @psalm-return array<array{yes: int, no: int, performance: float, title: mixed, lowest_note: mixed, highest_note: mixed, wideness: int, total?: int}>
	 */
	public function readFeedback(): array
	{
		$nc = new \NeoTransposer\Domain\NotesCalculator;

		$sql = <<<SQL
SELECT song.id_song, title, song.lowest_note, song.highest_note, count(*) fbs
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song
GROUP BY id_song
ORDER BY song.id_book, fbs DESC
SQL;

		$fbsongs = $this->dbConnection->fetchAllAssociative($sql);

		$feedback = [];

		foreach ($fbsongs as $song)
		{
			$yes = (int) $this->dbConnection->fetchOne('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', [$song['id_song']]);
			$no  = (int) $this->dbConnection->fetchOne('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', [$song['id_song']]);

			$feedback[$song['id_song']] = [
				'yes'			=> $yes,
				'no'			=> $no,
				'performance'	=> $yes / ($yes + $no),

				'title'			=> $song['title'],
				'lowest_note'	=> $song['lowest_note'],
				'highest_note'	=> $song['highest_note'],
				'wideness'		=> $nc->distanceWithOctave($song['highest_note'], $song['lowest_note']),
			];
			$feedback[$song['id_song']]['total'] = $feedback[$song['id_song']]['yes'] + $feedback[$song['id_song']]['no'];
		}

		return $feedback;
	}

	public function readUnhappyUsers(): array
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

		return $this->dbConnection->fetchAllAssociative($sql,[
			UnhappinessManager::UNHAPPY_THRESHOLD_PERF,
			UnhappinessManager::UNHAPPY_THRESHOLD_REPORTS
		]);
	}

	public function readGlobalPerfChronological(): array
    {
		$global_perf_chrono = [];
        $sql = <<<SQL
SELECT date(time) day
FROM transposition_feedback
GROUP BY day
ORDER BY day DESC
SQL;

		$days_with_feedback = $this->dbConnection->fetchAllAssociative($sql);

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
JOIN
(
  SELECT date(time) day, count(worked) d_yes, worked
  FROM transposition_feedback
  WHERE date(time) = '$day'
  AND worked=1
) sub_dyes
JOIN
(
  SELECT date(time) day, count(worked) d_no, worked
  FROM transposition_feedback
  WHERE date(time) = '$day'
  AND worked=0
) sub_dno
SQL;

			$global_perf_chrono[] = $this->dbConnection->fetchAllAssociative($sql)[0];

		}

		return $global_perf_chrono;
	}

	public function readSongsWithFeedback(): array
    {
		$sql = <<<SQL
SELECT nofb.id_book, nofb.nofb, total.total, book.lang_name FROM
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
JOIN book ON book.id_book = total.id_book
/* Trick for ES & PT book (the JOIN on NULL fails and does not appear, since
 * they have 100%), unnecessary in Mysql >= 8
 */
UNION
SELECT id_book, 0, COUNT(DISTINCT id_song), book.lang_name
FROM transposition_feedback
JOIN song USING (id_song)
JOIN book USING (id_book)
GROUP BY id_book
HAVING id_book=2 OR id_book=4
SQL;
		return $this->dbConnection->fetchAllAssociative($sql);
	}

	public function readMostActiveUsers(): array
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
		return $this->dbConnection->fetchAllAssociative($sql);
	}

	public function readGoodUsersChronological(): array
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
		return $this->dbConnection->fetchAllAssociative($sql);
	}

	/**
	 * Get the names of all countries in user.country by geo-locating any IP for
	 * each country.
     *
     * @todo This is inherently wrong. Getting country long name from some IP instead of from its short name already
     *       stored in DB is unreliable, because the IP might now belong to a different country.
	 */
	public function readCountryNamesList(\NeoTransposer\Domain\GeoIp\GeoIpResolver $geoIpResolver): array
	{
		if (!empty($this->countryNames))
		{
			return $this->countryNames;
		}

		//ONLY_FULL_GROUP_BY mode (default in MySQL>5.7) makes the query fail
		$this->dbConnection->query("SET @@sql_mode=''");
		$ips_for_country = $this->dbConnection->fetchAllAssociative('SELECT country, register_ip FROM user WHERE NOT country IS NULL GROUP BY country');
		$country_names = [];

		foreach ($ips_for_country as $ip)
		{
			try {
                if (!is_null($geoIpResolver->resolve($ip['register_ip'])->country()->names())) {
                    $country_names[$ip['country']] = $geoIpResolver->resolve($ip['register_ip'])->country()->names()['en'];
                }
			}
			catch (\NeoTransposer\Domain\GeoIp\GeoIpNotFoundException)
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
	public function readPerformanceByCountry(\NeoTransposer\Domain\GeoIp\GeoIpResolver $geoIpResolver): array
	{
		$sql = <<<SQL
select country, count(user.id_user) n
from user
join transposition_feedback on transposition_feedback.id_user = user.id_user
where not country is null
group by country
order by n desc
SQL;

		$countries = $this->dbConnection->fetchAllAssociative($sql);

		$performance = [];

		$country_names = $this->readCountryNamesList($geoIpResolver);

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

		$goodUsersCountryRaw = $this->dbConnection->fetchAllAssociative($sql);

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
			$countryPerformance = $this->dbConnection->fetchAllAssociative($sql);

			if ($countryPerformance[0]['total'] > 5)
			{
				$performance[$country] = $countryPerformance[0];
				$performance[$country]['country_name'] 	= @$country_names[$country];
				$performance[$country]['good_users']	= $goodUsersCountry[$country];
			}
		}

		usort($performance, function($a, $b)
		{
			return ($a['performance'] < $b['performance']) ? 1 : -1;
		});

		return $performance;
	}

	public function readDetailedFeedbackTransposition(string $detailedFeedbackDeployed): array
    {
		$sql = <<<SQL
SELECT transposition, count(*) fbs
FROM transposition_feedback
WHERE worked = 1
AND time > ?
GROUP BY transposition
ORDER BY fbs DESC
SQL;
        /** @todo Move that constant from AdminDashboard to ReadAdminMetrics (domain service) */
		$fbsByTransposition = $this->dbConnection->fetchAllAssociative($sql, [$detailedFeedbackDeployed]);

		$total = array_sum(array_column($fbsByTransposition, 'fbs'));
		foreach ($fbsByTransposition as &$fbs)
		{
			$fbs['fbs_relative'] = $fbs['fbs'] / $total;
		}

		return $fbsByTransposition;
	}

	public function readDetailedFeedbackPcStatus(): array
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

		return $this->dbConnection->fetchAllAssociative($sql);
	}

	public function readDetailedFeedbackCenteredScoreRate(): array
    {
		$sql = <<<SQL
SELECT id_song, title, time, centered_score_rate
FROM transposition_feedback
JOIN song USING (id_song)
WHERE transposition = 'centered2'
AND NOT centered_score_rate IS NULL
ORDER BY song.id_book, centered_score_rate DESC
SQL;

		return $this->dbConnection->fetchAllAssociative($sql);
	}

	public function readDetailedFeedbackDeviation(): array
    {
		$sql = <<<SQL
SELECT transposition, deviation_from_center, count(*) fbs
FROM transposition_feedback
WHERE NOT deviation_from_center IS NULL
GROUP BY transposition, deviation_from_center
ORDER BY deviation_from_center
SQL;

		return $this->dbConnection->fetchAllAssociative($sql);
	}

	public function readUsersByBook(int $totalUsers): array
	{
		$sql = <<<SQL
SELECT lang_name, id_book, count(id_user) users
FROM user
LEFT JOIN book USING (id_book)
GROUP BY user.id_book
ORDER BY users DESC
SQL;

		$users = $this->dbConnection->fetchAllAssociative($sql);
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

	public function readPerformanceByBook(array $allBooks): array
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

		foreach ($allBooks as $book)
		{
			$performance[$book->idBook()] = $this->aggregatePerformanceData(
				$this->dbConnection->fetchAllAssociative($sql, [$book->idBook()])
			);
		}

		return $performance;
	}

	public function readPerformanceByVoice(): array
    {
		$sql = <<<SQL
SELECT user.wizard_step1 AS voiceType, count(*) AS fbs, sum(worked) / count(*) AS performance
FROM `transposition_feedback`
JOIN user USING (id_user)
GROUP BY user.wizard_step1
SQL;
		return $this->dbConnection->fetchAllAssociative($sql);
	}
}
