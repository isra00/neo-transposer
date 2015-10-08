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
	 * Check songs that have lower_note > higher_note
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
				$output[] = $song['id_song'] . ' ' . $song['lowest_note'] . ' is higher than ' . $song['highest_note'] . '!!!';
			}

			if ($song['lowest_note'] == $song['highest_note'])
			{
				$output[] = $song['id_song'] . ' highest_note == lowest_note !!';
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
	 * Clear all the cached PDF All Songs Reports.
	 *
	 * Warning! This function does not work in Windows.
	 * 
	 * @param  NeoApp $app 	The NeoApp object.
	 * @return string 		Confirmation message or command output if any.
	 */
	public function clearPdfCache(NeoApp $app)
	{
		$dir = $app['root_dir']
		 . '/web/' . $app['neoconfig']['pdf_reports_dir'];

		exec("rm $dir/*", $output);
		return empty($output) ? 'All PDF reports removed succesfully' : $output;
	}
}