<?php

namespace NeoTransposer\Model;

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
	public function populateCountry()
	{
		$ips 	= $this->app['db']->fetchAll('SELECT register_ip FROM user WHERE country IS NULL');
		$reader = $this->app['geoIp2Reader'];

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

			$this->app['db']->update(
				'user',
				array('country' => $record->country->isoCode),
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

		$output = array();

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
			$output[] = 'NO inconsistences found :-)';
		}

		return implode("\n", $output);
	}

	public function checkUserLowerHigherNotes()
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
	 * Remove the minified CSS file, so that a new one will be generated in the
	 * next request.
	 * 
	 * @return string An unsignificant message for the admin.
	 */
	public function refreshCss()
	{
		$cache_file = $this->app['root_dir'] . '/web/static/compiled-' . $this->app['neoconfig']['css_cache'] . '.css';
		
		if (!file_exists($cache_file))
		{
			return 'CSS cache ' . $this->app['neoconfig']['css_cache'] . ' file is not present';
		}

		if (unlink($cache_file))
		{
			return 'Removed CSS cache file ' . $this->app['neoconfig']['css_cache'];
		}
	}

	/**
	 * Check that all songs have chords in correlative order starting by zero.
	 * 
	 * @return string Check results (to be displayed).
	 */
	public function checkChordOrder()
	{
		$chords = $this->app['db']->fetchAll(
			'SELECT * FROM `song_chord` ORDER BY id_song ASC, position ASC'
		);

		$output = array();

		$current_song = null;
		$last_position = null;
		foreach ($chords as $chord)
		{
			if ($current_song != $chord['id_song'])
			{
				$current_song = $chord['id_song'];

				if ($chord['position'] != 0)
				{
					$output[$chord['id_song']] = true;
				}
			}

			if ($chord['position'] != 0 && $chord['position'] != $last_position + 1)
			{
				$output[$chord['id_song']] = true;
			}

			$last_position = $chord['position'];
		}

		return empty($output)
			? 'NO inconsistences found :-)'
			: 'Songs with problems: ' . implode(', ', $output);
	}

	public function testAllTranspositions()
	{
		$test = new TestAllTranspositions($this->app);
		return $test->testAllTranspositions();
	}

	public function getVoiceRangeOfGoodUsers()
	{
		$goodUsers = $this->app['db']->fetchAll('SELECT id_user, wizard_step1, lowest_note, highest_note FROM user WHERE CAST(SUBSTRING(highest_note, LENGTH(highest_note)) AS UNSIGNED) > 1');
		$output = '';
		
		$nc = new NotesCalculator;

		foreach ($goodUsers as $user)
		{
			$output .= $user['id_user'] . ',' . $user['wizard_step1'] . ',' . $user['lowest_note'] . ","
			 . array_search($user['lowest_note'], $nc->numbered_scale) . ","
			 . $user['highest_note'] . ","
			 . array_search($user['highest_note'], $nc->numbered_scale) . "\n";
		}

		return $output;
	}

	public function detectOrphanChords()
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
}
