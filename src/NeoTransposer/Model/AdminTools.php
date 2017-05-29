<?php

namespace NeoTransposer\Model;

/**
 * Administrator's tools.
 */
class AdminTools extends \NeoTransposer\AppAccess
{
	protected $testAllTranspositionsBook = 2;

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
	 * - lowest_note_assembly > highest_note_assembly
	 * - lowest_note_assembly == highest_note_assembly
	 * - lowest_note_assembly < lowest_note
	 * - highest_note_assembly > highest_note
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

			if (!empty($song['lowest_note_assembly']) && !empty($song['highest_note_assembly']))
			{
				if ($song['lowest_note_assembly'] != $nc->lowestNote(array($song['lowest_note_assembly'], $song['highest_note_assembly'])))
				{
					$output[] = $song['id_song'] . ' assembly lowest_note ' . $song['lowest_note_assembly'] . ' is higher than ' . $song['highest_note_assembly'] . '!';
				}

				if ($song['lowest_note_assembly'] == $song['highest_note_assembly'])
				{
					$output[] = $song['id_song'] . ' highest_note_assembly == lowest_note_assembly!';
				}

				if (0 > $nc->distanceWithOctave($song['lowest_note_assembly'], $song['lowest_note']))
				{
					$output[] = $song['id_song'] . ' lowest_note_assembly < lowest_note!';
				}

				if (0 > $nc->distanceWithOctave($song['highest_note'], $song['highest_note_assembly']))
				{
					$output[] = $song['id_song'] . ' highest_note_assembly > highest_note!';
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


	/**
	 * A functional test for detecting changes in the transposition algorithm.
	 * It generates an AllSongsReport and compares it with a pre-stored result set.
	 * 
	 * @return string Check results (to be displayed).
	 */
	public function testAllTranspositions()
	{
		$testData = json_decode(
			file_get_contents($this->app['neoconfig']['test_all_transpositions_expected']),
			true
		);

		$this->app['neouser']->lowest_note  = $testData['singerLowestVoice'];
		$this->app['neouser']->highest_note = $testData['singerHighestVoice'];

		$sql = <<<SQL
SELECT id_song
FROM song 
WHERE id_book = ? 
ORDER BY id_song
SQL;

		$ids = $this->app['db']->fetchAll($sql, array($this->testAllTranspositionsBook));

		$allSongs = array();

		foreach ($ids as $id)
		{
			$song = TransposedSong::create($id['id_song'], $this->app);
			$song->transpose();

			$allSongs[] = $song;
		}

		$testResult = array();

		foreach ($allSongs as $transposedSong)
		{
			$testResult[$transposedSong->song->idSong] = array(
				'songLowestNote' 	=> $transposedSong->song->lowestNote,
				'songHighestNote' 	=> $transposedSong->song->highestNote,
				'centered1' => array(
					'offset' 			=> $transposedSong->transpositions[0]->offset,
					'lowestNote' 		=> $transposedSong->transpositions[0]->lowestNote,
					'highestNote' 		=> $transposedSong->transpositions[0]->highestNote,
					'score' 			=> $transposedSong->transpositions[0]->score,
					'capo' 				=> $transposedSong->transpositions[0]->getCapo(),
					'chords'			=> join(',', $transposedSong->transpositions[0]->chords)
				),
				'centered2' => array(
					'offset' 			=> $transposedSong->transpositions[1]->offset,
					'lowestNote' 		=> $transposedSong->transpositions[1]->lowestNote,
					'highestNote' 		=> $transposedSong->transpositions[1]->highestNote,
					'score' 			=> $transposedSong->transpositions[1]->score,
					'capo' 				=> $transposedSong->transpositions[1]->getCapo(),
					'chords'			=> join(',', $transposedSong->transpositions[1]->chords)
				)
			);

			if ($transposedSong->not_equivalent)
			{
				$testResult[$transposedSong->song->idSong]['notEquivalent'] = array(
					'offset' 			=> $transposedSong->not_equivalent->offset,
					'lowestNote' 		=> $transposedSong->not_equivalent->lowestNote,
					'highestNote' 		=> $transposedSong->not_equivalent->highestNote,
					'score' 			=> $transposedSong->not_equivalent->score,
					'capo' 				=> $transposedSong->not_equivalent->getCapo(),
					'deviationFromCentered' => $transposedSong->not_equivalent->deviationFromCentered,
					'chords'			=> join(',', $transposedSong->not_equivalent->chords),
				);
			}
		}

		$output = '';

		if ($missingSongs = array_diff(
			array_keys($testData['expectedResults']), 
			array_keys($testResult)
		))
		{
			$output .= '<strong>Missing songs: ' . join(', ', $missingSongs) . "</strong>\n";
		}

		foreach ($testResult as $idSong=>$result)
		{
			if ($difference = $this->diffTestResults($result, $testData['expectedResults'][$idSong]))
			{
				$output .= "\n<strong>Song #$idSong</strong>\n";
				foreach ($difference as $property=>$resultValue)
				{
					if (is_array($resultValue))
					{
						$output .= 'Transposition ' . $property . ":\n";

						foreach ($resultValue as $transProperty=>$transResultValue)
						{
							$output .= "\t$transProperty: expected <em>" . $testData['expectedResults'][$idSong][$property][$transProperty] . '</em> but got <em>' . $transResultValue . "</em>\n";
						}
					}
					else
					{
						if (isset($testData['expectedResults'][$idSong][$property]))
						{
							if (is_array($testData['expectedResults'][$idSong][$property]))
							{
								$testData['expectedResults'][$idSong][$property] = join('; ', $testData['expectedResults'][$idSong][$property]);
							}

							$output .= "$property: expected <em>" . ((string) $testData['expectedResults'][$idSong][$property]) . '</em> but got <em>' . $resultValue . "</em>\n";
						}
						else
						{
							$output .= "Unexpected property $property <em>" . $resultValue . "</em> not specified in test data\n";
						}
					}
				}
			}
		}

		return empty($output) ? 'Test SUCCESSFUL: song transpositions are identical to expected :-)' : $output;
	}

	protected function diffTestResults($actual, $expected)
	{
		$scalarProperties = array('songLowestNote', 'songHighestNote');
		$arrayProperties = array('centered1', 'centered2', 'notEquivalent');

		$diff = array_diff(
			array_intersect_key($actual,   array_flip($scalarProperties)),
			array_intersect_key($expected, array_flip($scalarProperties))
		);

		$transpositionsDiff = array();

		foreach (array_intersect_key($actual, array_flip($arrayProperties)) as $type=>$transposition)
		{
			if (!isset($expected[$type]))
			{
				$transpositionsDiff[$type] = '[unexpected]';
				continue;
			}

			if ($transDiff = array_diff($transposition, $expected[$type]))
			{
				$transpositionsDiff[$type] = $transDiff;
			}
		}

		if ($transpositionsDiff)
		{
			$diff = array_merge($diff, $transpositionsDiff);
		}

		$missingProperties = array_diff(array_keys($expected), array_keys($actual));

		if ($missingProperties)
		{
			$missingProperties = array_flip($missingProperties);
			foreach ($missingProperties as &$value)
			{
				$value = 'missing';
			}
			$diff = array_merge($diff, $missingProperties);
		}

		return $diff;
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
