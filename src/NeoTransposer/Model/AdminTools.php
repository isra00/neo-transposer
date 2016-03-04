<?php

namespace NeoTransposer\Model;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \NeoTransposer\NeoApp;

/**
 * Administrator's tools.
 */
class AdminTools
{
	/**
	 * Populate the country column of the user table with GeoIP.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp object.
	 * @return string                     Just a confirmation message.
	 */
	public function populateCountry(NeoApp $app)
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
	 * @param  \NeoTransposer\NeoApp $app The NeoApp object.
	 * @return string                     Check results, to be displayed.
	 */
	public function checkLowerHigherNotes(NeoApp $app)
	{
		$songs = $app['db']->fetchAll('SELECT * FROM song');

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

	/**
	 * Remove the minified CSS file, so that a new one will be generated in the
	 * next request.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp object.
	 * @return [type]                     An unsignificant message for the admin.
	 */
	public function refreshCss(NeoApp $app)
	{
		$cache_file = $app['root_dir'] . '/web/static/' . $app['neoconfig']['css_cache'] . '.css';
		
		if (!file_exists($cache_file))
		{
			return 'CSS cache ' . $app['neoconfig']['css_cache'] . ' file is not present';
		}

		if (unlink($cache_file))
		{
			return 'Removed CSS cache file ' . $app['neoconfig']['css_cache'];
		}
	}

	/**
	 * Check that all songs have chords in correlative order starting by zero.
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp object.
	 * @return string                     Check results (to be displayed).
	 */
	public function checkChordOrder(NeoApp $app)
	{
		$chords = $app['db']->fetchAll(
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

		return $output;
	}


	/**
	 * A functional test for detecting changes in the transposition algorithm.
	 * It generates an AllSongsReport and compares it with a pre-stored result set.
	 * 
	 * @todo Implementar detección de cambio de prioridad (p. ej., que la transposición esperada #0
	 *       ahora es #1 y la esperada #1 es 0).
	 * 
	 * @param  \NeoTransposer\NeoApp $app The NeoApp object.
	 * @return string                     Check results (to be displayed).
	 */
	public function testAllTranspositions(NeoApp $app)
	{
		$allSongsController = new \NeoTransposer\Controllers\AllSongsReport();

		$app['locale'] = 'es';

		//Beware that it will generate a report with the songs of the current locale only.
		$allSongs = $allSongsController->getAllTranspositions($app);

		$testData = json_decode(
			file_get_contents($app['neoconfig']['test_all_transpositions_expected']),
			true
		);

		$app['neouser']->lowest_note  = $testData['singerLowestVoice'];
		$app['neouser']->highest_note = $testData['singerHighestVoice'];

		$testResult = array();

		foreach ($allSongs as $transposedSong)
		{
			$testResult[$transposedSong->song->idSong] = array(
				'songLowestNote' 	=> $transposedSong->song->lowestNote,
				'songHighestNote' 	=> $transposedSong->song->highestNote,
				'offset' 			=> $transposedSong->transpositions[0]->offset,
				'lowestNote' 		=> $transposedSong->transpositions[0]->lowestNote,
				'highestNote' 		=> $transposedSong->transpositions[0]->highestNote,
				'score' 			=> $transposedSong->transpositions[0]->score,
				'capo' 				=> $transposedSong->transpositions[0]->getCapo(),
				'chords'			=> join(',', $transposedSong->transpositions[0]->chords)
			);
		}

		$output = '';

		foreach ($testResult as $idSong=>$result)
		{
			if ($difference = array_diff($result, $testData['expectedResults'][$idSong]))
			{
				$output .= "\n<strong>Song #$idSong</strong>\n";
				foreach ($difference as $property=>$resultValue)
				{
					$output .= "$property:\texpected <em>" . $testData['expectedResults'][$idSong][$property] . '</em> but got <em>' . $resultValue . "</em>\n";
				}
			}
		}

		return empty($output) ? 'Test <strong class="green">SUCCESSFUL</strong>: song transpositions are identical to expected :-)' : $output;
	}
}