<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\GeoIp\CountryNames;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use NeoTransposer\Domain\Service\UnhappinessManager;

final class AdminMetricsRepositoryMysql extends MysqlRepository implements AdminMetricsRepository
{
    private function selectArrays(string $sql, array $params = []): array
    {
        return array_map(fn ($row) => (array) $row, $this->dbConnection->select($sql, $params));
    }

    public function readUserCountTotal(): int
    {
        return (int) $this->dbConnection->scalar('SELECT COUNT(id_user) FROM user');
    }

    public function readUserCountGood(): int
    {
        return (int) $this->dbConnection->scalar('SELECT COUNT(id_user) FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
    }

    public function readGlobalPerformance(): array
    {
        $sql_gp_all = <<<'SQL'
SELECT worked, count(worked) n
FROM transposition_feedback
GROUP BY worked
WITH ROLLUP
SQL;

        $sql_gp_good_users = <<<'SQL'
SELECT worked, count(worked) n
FROM transposition_feedback
JOIN user on transposition_feedback.id_user = user.id_user AND CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1
GROUP BY worked
WITH ROLLUP
SQL;

        $global_performance = [
            'all'   => $this->selectArrays($sql_gp_all),
            'goods' => $this->selectArrays($sql_gp_good_users),
        ];

        foreach ($global_performance as &$raw_data) {
            $raw_data = $this->aggregatePerformanceData($raw_data);
        }

        return $global_performance;
    }

    public function readUsersReportingFeedback(): array
    {
        $sqlUsersReportingFb = <<<'SQL'
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
        $this->dbConnection->statement("SET sql_mode=''");

        return (array) $this->dbConnection->selectOne($sqlUsersReportingFb);
    }

    /**
     * @return array{yes: int, no: int, total: int}
     */
    private function aggregatePerformanceData(array $raw_data): array
    {
        $answers = ['no', 'yes'];

        $feedback_data = ['yes'=>0, 'no'=>0, 'total'=>0];
        foreach ($raw_data as &$row) {
            $key = is_null($row['worked']) ? 'total' : $answers[$row['worked']];
            $feedback_data[$key] = $row['n'];
        }

        return $feedback_data;
    }

    public function readSongAvailability(): array
    {
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql);
    }

    /**
     * @return array<array{yes: int, no: int, performance: float, title: mixed, lowest_note: mixed, highest_note: mixed, wideness: int, total?: int}>
     */
    public function readFeedback(): array
    {
        $nc = new NotesCalculator();

        $sql = <<<'SQL'
SELECT song.id_song, title, song.lowest_note, song.highest_note, count(*) fbs
FROM transposition_feedback
JOIN song ON transposition_feedback.id_song = song.id_song
GROUP BY id_song
ORDER BY song.id_book, fbs DESC
SQL;

        $fbsongs = $this->selectArrays($sql);

        $feedback = [];

        foreach ($fbsongs as $song) {
            $yes = (int) $this->dbConnection->scalar('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=1', [$song['id_song']]);
            $no = (int) $this->dbConnection->scalar('select count(worked) from transposition_feedback where id_song = ? group by worked having worked=0', [$song['id_song']]);

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
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql, [
            UnhappinessManager::UNHAPPY_THRESHOLD_PERF,
            UnhappinessManager::UNHAPPY_THRESHOLD_REPORTS,
        ]);
    }

    public function readGlobalPerfChronological(): array
    {
        $global_perf_chrono = [];
        $sql = <<<'SQL'
SELECT date(time) day
FROM transposition_feedback
GROUP BY day
ORDER BY day DESC
SQL;

        $days_with_feedback = $this->selectArrays($sql);

        foreach ($days_with_feedback as $day) {
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

            $global_perf_chrono[] = $this->selectArrays($sql)[0];

        }

        return $global_perf_chrono;
    }

    public function readSongsWithFeedback(): array
    {
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql);
    }

    public function readMostActiveUsers(): array
    {
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql);
    }

    public function readGoodUsersChronological(): array
    {
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql);
    }

    /**
     * @return list<mixed>
     */
    public function readPerformanceByCountry(): array
    {
        $sql = <<<'SQL'
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

        $goodUsersCountryRaw = $this->selectArrays($sql);

        $goodUsersCountry = [];
        foreach ($goodUsersCountryRaw as $row) {
            $goodUsersCountry[$row['country']] = $row['good'] / $row['total'];
        }

        $sql = <<<'SQL'
SELECT
  user.country,
  SUM(CASE WHEN worked = 1 THEN 1 ELSE 0 END) AS yes,
  SUM(CASE WHEN worked = 0 THEN 1 ELSE 0 END) AS no,
  COUNT(*) AS total,
  SUM(CASE WHEN worked = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100 AS performance
FROM transposition_feedback
JOIN user ON transposition_feedback.id_user = user.id_user
WHERE user.country IS NOT NULL
GROUP BY user.country
HAVING total > 5
ORDER BY performance DESC
SQL;

        $rows = $this->selectArrays($sql);

        $performance = [];
        foreach ($rows as $row) {
            $row['country_name'] = CountryNames::nameOf($row['country']);
            $row['good_users'] = $goodUsersCountry[$row['country']] ?? 0;
            $performance[] = $row;
        }

        return $performance;
    }

    public function readDetailedFeedbackTransposition(string $detailedFeedbackDeployed): array
    {
        $sql = <<<'SQL'
SELECT transposition, count(*) fbs
FROM transposition_feedback
WHERE worked = 1
AND time > ?
GROUP BY transposition
ORDER BY fbs DESC
SQL;
        $fbsByTransposition = $this->selectArrays($sql, [$detailedFeedbackDeployed]);

        $total = array_sum(array_column($fbsByTransposition, 'fbs'));
        foreach ($fbsByTransposition as &$fbs) {
            $fbs['fbs_relative'] = $fbs['fbs'] / $total;
        }

        return $fbsByTransposition;
    }

    public function readDetailedFeedbackPcStatus(): array
    {
        $sql = <<<'SQL'
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

        return $this->selectArrays($sql);
    }

    public function readDetailedFeedbackCenteredScoreRate(): array
    {
        $sql = <<<'SQL'
SELECT id_song, title, time, centered_score_rate
FROM transposition_feedback
JOIN song USING (id_song)
WHERE transposition = 'centered2'
AND NOT centered_score_rate IS NULL
ORDER BY song.id_book, centered_score_rate DESC
SQL;

        return $this->selectArrays($sql);
    }

    public function readDetailedFeedbackDeviation(): array
    {
        $sql = <<<'SQL'
SELECT transposition, deviation_from_center, count(*) fbs
FROM transposition_feedback
WHERE NOT deviation_from_center IS NULL
GROUP BY transposition, deviation_from_center
ORDER BY deviation_from_center
SQL;

        return $this->selectArrays($sql);
    }

    public function readUsersByBook(int $totalUsers): array
    {
        $sql = <<<'SQL'
SELECT lang_name, id_book, count(id_user) users
FROM user
LEFT JOIN book USING (id_book)
GROUP BY user.id_book
ORDER BY users DESC
SQL;

        $users = $this->selectArrays($sql);
        $usersBookId = [];

        foreach ($users as &$book) {
            $book['percent'] = ((int) $book['users'] / $totalUsers) * 100;

            if (empty($book['lang_name'])) {
                $book['lang_name'] = '(not set)';
            }

            $usersBookId[$book['id_book']] = $book;
        }

        return $usersBookId;
    }

    public function readPerformanceByBook(array $allBooks): array
    {
        $sql = <<<'SQL'
SELECT
  id_book,
  SUM(CASE WHEN worked = 1 THEN 1 ELSE 0 END) AS yes,
  SUM(CASE WHEN worked = 0 THEN 1 ELSE 0 END) AS no,
  COUNT(*) AS total
FROM transposition_feedback
JOIN song USING (id_song)
GROUP BY id_book
SQL;

        $rows = $this->selectArrays($sql);
        $performance = [];

        foreach ($rows as $row) {
            $performance[$row['id_book']] = [
                'yes'   => (int) $row['yes'],
                'no'    => (int) $row['no'],
                'total' => (int) $row['total'],
            ];
        }

        return $performance;
    }

    public function readPerformanceByVoice(): array
    {
        $sql = <<<'SQL'
SELECT user.wizard_step1 AS voiceType, count(*) AS fbs, sum(worked) / count(*) AS performance
FROM `transposition_feedback`
JOIN user USING (id_user)
GROUP BY user.wizard_step1
SQL;

        return $this->selectArrays($sql);
    }

    public function readSongsWithUrl(): array
    {
        $sql = <<<'SQL'
SELECT book.lang_name, COUNT(*) total, SUM(IF(url IS NOT NULL, 1, 0)) AS with_url
FROM song
JOIN book USING (id_book)
GROUP BY id_book
SQL;

        return $this->selectArrays($sql);
    }
}
