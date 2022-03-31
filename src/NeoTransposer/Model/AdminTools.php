<?php

namespace NeoTransposer\Model;

use GeoIp2\Exception\AddressNotFoundException;

/**
 * Administrator's tools.
 */
class AdminTools extends \NeoTransposer\AppAccess
{

	/**
	 * Populate the country column of the user table with GeoIP.
	 * 
	 * @return string Just a confirmation message.
	 */
	public function populateCountry(): string
    {
		$ips 	       = $this->app['db']->fetchAll('SELECT register_ip FROM user WHERE country IS NULL');
        $geoIpResolver = $this->app[\NeoTransposer\Domain\GeoIp\GeoIpResolver::class];

		foreach ($ips as $ip)
		{
			$ip = $ip['register_ip'];

			if (!strlen(trim($ip)))
			{
				continue;
			}

			try
			{
                $location = $geoIpResolver->resolve($ip);
			}
			catch (\NeoTransposer\Domain\GeoIp\GeoIpNotFoundException $e)
			{
				continue;
			}

			$this->app['db']->update(
				'user',
				array('country' => $location->country()->isoCode()),
				array('register_ip' => $ip)
			);
		}

		return 'user.country populated for ' . count($ips) . ' IPs';
	}

	/**
	 * Check songs that have one of the following conditions:
	 * - lowest_note > highest_note 
	 * - lowest_note == highest_note
	 * - people_lowest_note > people_highest_note
	 * - people_lowest_note == people_highest_note
	 * - people_lowest_note < lowest_note
	 * - people_highest_note > highest_note
	 * 
	 * @return string Check results, to be displayed.
	 */
	public function checkLowerHigherNotes()
	{
		$songs = $this->app['db']->fetchAll('SELECT * FROM song');

		$nc = new NotesCalculator;

		$output = [];

		foreach ($songs as $song)
		{
			if ($song['lowest_note'] != $nc->lowestNote(array($song['lowest_note'], $song['highest_note'])))
			{
				$output[] = $song['id_song'] . ' ' . $song['lowest_note'] . ' is higher than ' . $song['highest_note'] . '!';
			}

			if ($song['lowest_note'] == $song['highest_note'])
			{
				$output[] = $song['id_song'] . ' highest_note == lowest_note!';
			}

			if (!empty($song['people_lowest_note']) && !empty($song['people_highest_note']))
			{
				if ($song['people_lowest_note'] != $nc->lowestNote(array($song['people_lowest_note'], $song['people_highest_note'])))
				{
					$output[] = $song['id_song'] . ' assembly lowest_note ' . $song['people_lowest_note'] . ' is higher than ' . $song['people_highest_note'] . '!';
				}

				if ($song['people_lowest_note'] == $song['people_highest_note'])
				{
					$output[] = $song['id_song'] . ' people_highest_note == people_lowest_note!';
				}

				if (0 > $nc->distanceWithOctave($song['people_lowest_note'], $song['lowest_note']))
				{
					$output[] = $song['id_song'] . ' people_lowest_note < lowest_note!';
				}

				if (0 > $nc->distanceWithOctave($song['highest_note'], $song['people_highest_note']))
				{
					$output[] = $song['id_song'] . ' people_highest_note > highest_note!';
				}
			}
		}

		if (empty($output))
		{
			$output[] = 'NO inconsistencies found :-)';
		}

		return implode("\n", $output);
	}

	public function checkUserLowerHigherNotes(): string
	{
		$users = $this->app['db']->fetchAll('SELECT id_user, email, lowest_note, highest_note FROM user');

		$nc = new NotesCalculator;

		foreach ($users as $user)
		{
			if (!empty($user['lowest_note']) && !empty($user['highest_note']))
			{
				if ($user['lowest_note'] != $nc->lowestNote(array($user['lowest_note'], $user['highest_note'])))
				{
					$output[] = '#' . $user['id_user'] . ' lowest ' . $user['lowest_note'] . ' > highest ' . $user['highest_note'] . '!';
				}

				if ($user['lowest_note'] == $user['highest_note'])
				{
					$output[] = '#' . $user['id_user'] . ' highest_note == lowest_note (' . $user['lowest_note'] . ')';
				}
			}
		}

		if (empty($output))
		{
			$output[] = 'NO inconsistences found :-)';
		}

		return implode("\n", $output);
	}

	/**
	 * Re-compile the CSS file. Will not delete old compiled files.
	 * 
	 * @return string An unsignificant message for the admin.
	 */
	public function refreshCss()
	{
		$serveCssController = new \NeoTransposer\Controllers\ServeCss;
		return 'Generated new file ' . $serveCssController->get($this->app)->getTargetUrl();
	}

	/**
	 * Delete all compiled-*.css files except the one refered to in config.php
	 */
	public function removeOldCompiledCss(): string
	{
		$serveCssController = new \NeoTransposer\Controllers\ServeCss;
		$fileScheme = $serveCssController->min_file;
		$cssDir = realpath('.' . dirname($fileScheme));
		chdir($cssDir);
		$currentFile = sprintf($fileScheme, $this->app['neoconfig']['css_cache']);

		$allCssFiles = glob(sprintf(basename($fileScheme), '*'));
		$deletedCounter = 0;
		foreach ($allCssFiles as $file)
		{
			if ($file != basename($currentFile))
			{
				unlink($cssDir . '/' . $file);
				$deletedCounter++;
			}
		}
		return "Deleted $deletedCounter old CSS files";
	}

    /**
     * Check that all songs have chords in correlative order starting by zero.
     *
     * @return array|null Check results (to be displayed).
     */
	public function checkChordOrder(): ?array
	{
		$chords = $this->app['db']->fetchAll(
			'SELECT * FROM `song_chord` ORDER BY id_song ASC, position ASC'
		);

		$incorrect = [];

		$current_song = null;
		$last_position = null;
		foreach ($chords as $chord)
		{
			if ($current_song != $chord['id_song'])
			{
				$current_song = $chord['id_song'];

				if ($chord['position'] != 0)
				{
					$incorrect[$chord['id_song']] = true;
				}
			}

			if ($chord['position'] != 0 && $chord['position'] != $last_position + 1)
			{
				$incorrect[$chord['id_song']] = true;
			}

			$last_position = $chord['position'];
		}

        return $incorrect;
	}

    public function checkChordOrderHumanFriendly(): string
    {
        $incorrect = $this->checkChordOrder();

		return empty($incorrect)
			? 'NO inconsistencies found :-)'
			: 'Songs with problems: ' . implode(', ', $incorrect);
    }

	public function testAllTranspositions(): string
	{
		$test = new TestAllTranspositions($this->app);
		return $test->testAllTranspositions();
	}

	public function getVoiceRangeOfGoodUsers(): string
	{
		$goodUsers = $this->app['db']->fetchAll('SELECT id_user, wizard_step1, lowest_note, highest_note FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
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

	public function detectOrphanChords(): string
	{
		$sql = <<<SQL
SELECT song_chord.id_song id_song FROM song_chord
LEFT JOIN song ON song.id_song = song_chord.id_song
WHERE song.id_song IS NULL
SQL;
		$orphanIdSongs = array_column($this->app['db']->fetchAll($sql), 'id_song');

		return (empty($orphanIdSongs))
			? 'Good! No orphan chord detected.'
			: count($orphanIdSongs) . ' orphan id_song detected! Remove them with'
				. "\nDELETE FROM song_chord WHERE id_song IN (" . implode(', ', $orphanIdSongs) . ')';
	}

	public function getPerformanceByNumberOfFeedbacks(): string
	{
		$sql = <<<SQL
select fbs AS num_of_fbs, count(fbs) AS num_of_users, avg(performance) AS avg_perf
from (
 SELECT id_user, count(*) fbs, sum(worked) / count(*) as performance
 FROM `transposition_feedback`
 group by id_user
 order by fbs desc
) st
group by fbs desc
SQL;
		$data = $this->app['db']->fetchAll($sql);
		$output = "# of FBs,# of users,AVG performance\n";
		foreach ($data as $row)
		{
			$output .= implode(',', $row) . "\n";
		}

		return $output;
	}

	public function diffTranslations(): string
	{
		$languages = $this->app['neoconfig']['languages'];

		$transSpanish = include $languages['es']['file'];

		$diff = [];
		foreach ($languages as $lang=>$langDetails)
		{
			if (isset($langDetails['file']) && 'es' != $lang)
			{
				$trans = include $langDetails['file'];
				$diff[$lang] = array_diff(array_keys($transSpanish), array_keys($trans));
			}
		}

		return "TRANSLATION STRINGS IN SPANISH BUT NOT IN OTHER LANGUAGES:\n\n" . print_r($diff, true);
	}
}
