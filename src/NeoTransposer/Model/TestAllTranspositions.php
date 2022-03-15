<?php

namespace NeoTransposer\Model;

/**
 * A functional test for detecting changes in the transposition algorithm.
 * It generates an AllSongsReport for book and compares it with a pre-stored result set.
 * 
 */
class TestAllTranspositions extends \NeoTransposer\AppAccess
{
	protected $testAllTranspositionsBook = 2;

	/**
	 * Perform the test.
	 * 
	 * @return string Test results (to be displayed).
	 */
	public function testAllTranspositions()
	{
		$testData = json_decode(
			file_get_contents($this->app['neoconfig']['test_all_transpositions_expected' . (($this->app['neoconfig']['people_compatible']) ? '_pc' : '')]),
			true
		);

		$testResult = $this->generateActualTestResult($testData);

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
			if (isset($testData['expectedResults'][$idSong]) && $difference = $this->diffTestResults($result, $testData['expectedResults'][$idSong]))
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
								$testData['expectedResults'][$idSong][$property] = '[' . join('; ', $testData['expectedResults'][$idSong][$property]) . ']';
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

	protected function generateActualTestResult(array $testData)
	{
		$this->app['neouser']->range = new NotesRange(
			$testData['singerLowestVoice'], 
			$testData['singerHighestVoice']
		);

		$sql = <<<SQL
SELECT id_song
FROM song 
WHERE id_book = ? 
ORDER BY id_song
SQL;

		$ids = $this->app['db']->fetchAll($sql, array($this->testAllTranspositionsBook));

		$allSongs = [];

        $transposedSongFactory = new TransposedSongFactory($this->app);

		foreach ($ids as $id)
		{
            $song = $transposedSongFactory->createTransposedSongFromSongId($id['id_song']);

			$song->transpose();

			$allSongs[] = $song;
		}

		$testResult = [];

		foreach ($allSongs as $transposedSong)
		{
			$testResult[$transposedSong->song->idSong] = array(
				'songLowestNote' 	=> $transposedSong->song->range->lowest,
				'songHighestNote' 	=> $transposedSong->song->range->highest,
				'centered1' => array(
					'offset' 			=> $transposedSong->transpositions[0]->offset,
					'lowestNote' 		=> $transposedSong->transpositions[0]->range->lowest,
					'highestNote' 		=> $transposedSong->transpositions[0]->range->highest,
					'score' 			=> $transposedSong->transpositions[0]->score,
					'capo' 				=> $transposedSong->transpositions[0]->getCapo(),
					'chords'			=> join(',', $transposedSong->transpositions[0]->chords)
				),
				'centered2' => array(
					'offset' 			=> $transposedSong->transpositions[1]->offset,
					'lowestNote' 		=> $transposedSong->transpositions[1]->range->lowest,
					'highestNote' 		=> $transposedSong->transpositions[1]->range->highest,
					'score' 			=> $transposedSong->transpositions[1]->score,
					'capo' 				=> $transposedSong->transpositions[1]->getCapo(),
					'chords'			=> join(',', $transposedSong->transpositions[1]->chords)
				)
			);

			if ($transposedSong->not_equivalent)
			{
				$testResult[$transposedSong->song->idSong]['notEquivalent'] = array(
					'offset' 			=> $transposedSong->not_equivalent->offset,
					'lowestNote' 		=> $transposedSong->not_equivalent->range->lowest,
					'highestNote' 		=> $transposedSong->not_equivalent->range->highest,
					'score' 			=> $transposedSong->not_equivalent->score,
					'capo' 				=> $transposedSong->not_equivalent->getCapo(),
					'deviationFromCentered' => $transposedSong->not_equivalent->deviationFromCentered,
					'chords'			=> join(',', $transposedSong->not_equivalent->chords),
				);
			}

			if ($this->app['neoconfig']['people_compatible'])
			{
				$testResult[$transposedSong->song->idSong]['peopleCompatibleStatus'] = $transposedSong->peopleCompatibleStatus;

				if ($transposedSong->peopleCompatible)
				{
					$testResult[$transposedSong->song->idSong]['peopleCompatible'] = [
						'offset' 			=> $transposedSong->peopleCompatible->offset,
						'lowestNote' 		=> $transposedSong->peopleCompatible->range->lowest,
						'highestNote' 		=> $transposedSong->peopleCompatible->range->highest,
						'score' 			=> $transposedSong->peopleCompatible->score,
						'capo' 				=> $transposedSong->peopleCompatible->getCapo(),
						'deviationFromCentered' => $transposedSong->peopleCompatible->deviationFromCentered,
						'chords'			=> join(',', $transposedSong->peopleCompatible->chords),
						'peopleLowestNote' 	=> $transposedSong->peopleCompatible->peopleRange->lowest,
						'peopleHighestNote' => $transposedSong->peopleCompatible->peopleRange->highest,
					];
				}
			}
		}

		return $testResult;
	}

	protected function diffTestResults($actual, $expected)
	{
		$scalarProperties = ['songLowestNote', 'songHighestNote'];
		$arrayProperties  = ['centered1', 'centered2', 'notEquivalent'];

		if ($this->app['neoconfig']['people_compatible'])
		{
			$scalarProperties[] = 'peopleCompatibleStatus';
			$arrayProperties[]  = 'peopleCompatible';
		}

		$diff = @array_diff(
			array_intersect_key($actual,   array_flip($scalarProperties)),
			array_intersect_key($expected, array_flip($scalarProperties))
		);

		$transpositionsDiff = [];

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
			$diff = $diff
				? array_merge($diff, $transpositionsDiff)
				: null;
		}

		$missingProperties = $expected 
			? array_diff(array_keys($expected), array_keys($actual))
			: false;

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
}