<?php

namespace NeoTransposerApp\Domain\AdminTasks;

use NeoTransposerApp\Domain\TransposedSong;
use NeoTransposerApp\Domain\ValueObject\NotesRange;
use NeoTransposerWeb\NeoApp;

/**
 * A functional test for detecting changes in the transposition algorithm.
 * It generates an AllSongsReport for book and compares it with a pre-stored result set.
 */
class TestAllTranspositions implements AdminTask
{
    public const TEST_ALL_TRANSPOSITIONS_BOOK = 2;

	protected $app;
    protected $fileExpectedResults;
    protected $fileExpectedWithPeopleCompatibleResults;

	public function __construct(NeoApp $app, string $fileExpected, string $fileExpectedWithPeopleCompatible)
	{
		$this->app = $app;
        $this->fileExpectedResults = $fileExpected;
        $this->fileExpectedWithPeopleCompatibleResults = $fileExpectedWithPeopleCompatible;
    }

    /**
     * Perform the test.
     *
     * @return string Test results (to be displayed).
     */
    public function run(): string
    {
        $testData = json_decode(
            file_get_contents(
                $this->app['neoconfig']['people_compatible'] ? $this->fileExpectedWithPeopleCompatibleResults : $this->fileExpectedResults
            ),
            true
        );

        $testResult = $this->generateActualTestResult($testData);

        $output = '';

        if ($missingSongs = array_diff(
            array_keys($testData['expectedResults']),
            array_keys($testResult)
        )
        ) {
            $output .= '<strong>Missing songs: ' . join(', ', $missingSongs) . "</strong>\n";
        }

        foreach ($testResult as $idSong => $result) {
            if (isset($testData['expectedResults'][$idSong]) && $difference = $this->diffTestResults(
                    $result,
                    $testData['expectedResults'][$idSong]
                )) {
                $output .= "\n<strong>Song #$idSong</strong>\n";
                foreach ($difference as $property => $resultValue) {
                    if (is_array($resultValue)) {
                        $output .= 'Transposition ' . $property . ":\n";

                        foreach ($resultValue as $transProperty => $transResultValue) {
                            $output .= "\t$transProperty: expected <em>" . $testData['expectedResults'][$idSong][$property][$transProperty] . '</em> but got <em>' . $transResultValue . "</em>\n";
                        }
                    } else {
                        if (isset($testData['expectedResults'][$idSong][$property])) {
                            if (is_array($testData['expectedResults'][$idSong][$property])) {
                                $testData['expectedResults'][$idSong][$property] = '[' . join(
                                        '; ',
                                        $testData['expectedResults'][$idSong][$property]
                                    ) . ']';
                            }

                            $output .= "$property: expected <em>" . ((string)$testData['expectedResults'][$idSong][$property]) . '</em> but got <em>' . $resultValue . "</em>\n";
                        } else {
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
        $sql = <<<SQL
SELECT id_song
FROM song
WHERE id_book = ?
ORDER BY id_song
SQL;

        $ids = $this->app['db']->fetchAll($sql, [self::TEST_ALL_TRANSPOSITIONS_BOOK]);

        $allSongs = [];

        foreach ($ids as $id) {
            $song = TransposedSong::fromDb($id['id_song'], $this->app);

            $song->transpose(
                new NotesRange(
                    $testData['singerLowestVoice'],
                    $testData['singerHighestVoice']
                )
            );

            $allSongs[] = $song;
        }

        $testResult = [];

        foreach ($allSongs as $transposedSong) {
            $testResult[$transposedSong->song->idSong] = [
                'songLowestNote'  => $transposedSong->song->range->lowest(),
                'songHighestNote' => $transposedSong->song->range->highest(),
                'centered1'       => [
                    'offset'      => $transposedSong->transpositions[0]->offset,
                    'lowestNote'  => $transposedSong->transpositions[0]->range->lowest(),
                    'highestNote' => $transposedSong->transpositions[0]->range->highest(),
                    'score'       => $transposedSong->transpositions[0]->score,
                    'capo'        => $transposedSong->transpositions[0]->getCapo(),
                    'chords'      => join(',', $transposedSong->transpositions[0]->chords)
                ],
                'centered2'       => [
                    'offset'      => $transposedSong->transpositions[1]->offset,
                    'lowestNote'  => $transposedSong->transpositions[1]->range->lowest(),
                    'highestNote' => $transposedSong->transpositions[1]->range->highest(),
                    'score'       => $transposedSong->transpositions[1]->score,
                    'capo'        => $transposedSong->transpositions[1]->getCapo(),
                    'chords'      => join(',', $transposedSong->transpositions[1]->chords)
                ]
            ];

            if ($transposedSong->not_equivalent) {
                $testResult[$transposedSong->song->idSong]['notEquivalent'] = [
                    'offset'                => $transposedSong->not_equivalent->offset,
                    'lowestNote'            => $transposedSong->not_equivalent->range->lowest(),
                    'highestNote'           => $transposedSong->not_equivalent->range->highest(),
                    'score'                 => $transposedSong->not_equivalent->score,
                    'capo'                  => $transposedSong->not_equivalent->getCapo(),
                    'deviationFromCentered' => $transposedSong->not_equivalent->deviationFromCentered,
                    'chords'                => join(',', $transposedSong->not_equivalent->chords),
                ];
            }

            if ($this->app['neoconfig']['people_compatible']) {
                $testResult[$transposedSong->song->idSong]['peopleCompatibleStatus'] = $transposedSong->getPeopleCompatibleStatus(
                );

                if ($peopleCompatibleTransposition = $transposedSong->getPeopleCompatible()) {
                    $testResult[$transposedSong->song->idSong]['peopleCompatible'] = [
                        'offset'                => $peopleCompatibleTransposition->offset,
                        'lowestNote'            => $peopleCompatibleTransposition->range->lowest(),
                        'highestNote'           => $peopleCompatibleTransposition->range->highest(),
                        'score'                 => $peopleCompatibleTransposition->score,
                        'capo'                  => $peopleCompatibleTransposition->getCapo(),
                        'deviationFromCentered' => $peopleCompatibleTransposition->deviationFromCentered,
                        'chords'                => join(',', $peopleCompatibleTransposition->chords),
                        'peopleLowestNote'      => $peopleCompatibleTransposition->peopleRange->lowest(),
                        'peopleHighestNote'     => $peopleCompatibleTransposition->peopleRange->highest(),
                    ];
                }
            }
        }

        return $testResult;
    }

    protected function diffTestResults($actual, $expected)
    {
        $scalarProperties = ['songLowestNote', 'songHighestNote'];
        $arrayProperties = ['centered1', 'centered2', 'notEquivalent'];

        if ($this->app['neoconfig']['people_compatible']) {
            $scalarProperties[] = 'peopleCompatibleStatus';
            $arrayProperties[] = 'peopleCompatible';
        }

        $diff = @array_diff(
            array_intersect_key($actual, array_flip($scalarProperties)),
            array_intersect_key($expected, array_flip($scalarProperties))
        );

        $transpositionsDiff = [];

        foreach (array_intersect_key($actual, array_flip($arrayProperties)) as $type => $transposition) {
            if (!isset($expected[$type])) {
                $transpositionsDiff[$type] = '[unexpected]';
                continue;
            }

            if ($transDiff = array_diff($transposition, $expected[$type])) {
                $transpositionsDiff[$type] = $transDiff;
            }
        }

        if ($transpositionsDiff) {
            $diff = $diff
                ? array_merge($diff, $transpositionsDiff)
                : null;
        }

        $missingProperties = $expected
            ? array_diff(array_keys($expected), array_keys($actual))
            : false;

        if ($missingProperties) {
            $missingProperties = array_flip($missingProperties);
            foreach ($missingProperties as &$value) {
                $value = 'missing';
            }
            /** @todo Remove @ when upgrading to PHP7.4 (it should be possible to call array_merge with a null value) */
            $diff = @array_merge($diff, $missingProperties);
        }

        return $diff;
    }
}
