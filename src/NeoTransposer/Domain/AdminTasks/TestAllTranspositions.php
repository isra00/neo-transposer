<?php

namespace NeoTransposer\Domain\AdminTasks;

use Illuminate\Support\Facades\DB;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\ValueObject\NotesRange;

/**
 * A functional test for detecting changes in the transposition algorithm.
 * It generates an AllSongsReport for book and compares it with a pre-stored result set.
 */
final class TestAllTranspositions implements AdminTask
{
    final public const TEST_ALL_TRANSPOSITIONS_BOOK = 2;

    /**
     * Perform the test.
     *
     * @return string Test results (to be displayed).
     */
    public function run(): string
    {
        $testData = json_decode(
            file_get_contents(config('nt.test_all_transpositions_expected')),
            true
        );

        $testResult = $this->generateActualTestResult($testData);

        $output = '';

        if ($missingSongs = array_diff(
            array_keys($testData['expectedResults']),
            array_keys($testResult)
        )
        ) {
            $output .= '<strong>Missing songs: ' . implode(', ', $missingSongs) . "</strong>\n";
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
                    } elseif (isset($testData['expectedResults'][$idSong][$property])) {
                        if (is_array($testData['expectedResults'][$idSong][$property])) {
                            $testData['expectedResults'][$idSong][$property] = '[' . implode(
                                '; ',
                                $testData['expectedResults'][$idSong][$property]
                            ) . ']';
                        }
                        $output .= "$property: expected <em>" . ($testData['expectedResults'][$idSong][$property]) . '</em> but got <em>' . $resultValue . "</em>\n";
                    } else {
                        $output .= "Unexpected property $property <em>" . $resultValue . "</em> not specified in test data\n";
                    }
                }
            }
        }

        return empty($output) ? 'Test SUCCESSFUL: song transpositions are identical to expected :-)' : $output;
    }

    private function generateActualTestResult(array $testData)
    {
        $songIds = DB::table('song')
            ->where('id_book', self::TEST_ALL_TRANSPOSITIONS_BOOK)
            ->orderBy('id_song')
            ->pluck('id_song');

        $allSongs = [];

        foreach ($songIds as $idSong) {
            $song = TransposedSong::fromDbById($idSong);

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
                'songLowestNote'  => $transposedSong->song->range->lowest,
                'songHighestNote' => $transposedSong->song->range->highest,
                'centered1'       => [
                    'offset'      => $transposedSong->transpositionsCentered[0]->offset,
                    'lowestNote'  => $transposedSong->transpositionsCentered[0]->range->lowest,
                    'highestNote' => $transposedSong->transpositionsCentered[0]->range->highest,
                    'score'       => $transposedSong->transpositionsCentered[0]->score,
                    'capo'        => $transposedSong->transpositionsCentered[0]->getCapo(),
                    'chords'      => implode(',', $transposedSong->transpositionsCentered[0]->chords),
                ],
                'centered2'       => [
                    'offset'      => $transposedSong->transpositionsCentered[1]->offset,
                    'lowestNote'  => $transposedSong->transpositionsCentered[1]->range->lowest,
                    'highestNote' => $transposedSong->transpositionsCentered[1]->range->highest,
                    'score'       => $transposedSong->transpositionsCentered[1]->score,
                    'capo'        => $transposedSong->transpositionsCentered[1]->getCapo(),
                    'chords'      => implode(',', $transposedSong->transpositionsCentered[1]->chords),
                ],
            ];

            if ($transposedSong->transpositionEasierNotEquivalent) {
                $testResult[$transposedSong->song->idSong]['notEquivalent'] = [
                    'offset'                => $transposedSong->transpositionEasierNotEquivalent->offset,
                    'lowestNote'            => $transposedSong->transpositionEasierNotEquivalent->range->lowest,
                    'highestNote'           => $transposedSong->transpositionEasierNotEquivalent->range->highest,
                    'score'                 => $transposedSong->transpositionEasierNotEquivalent->score,
                    'capo'                  => $transposedSong->transpositionEasierNotEquivalent->getCapo(),
                    'deviationFromCentered' => $transposedSong->transpositionEasierNotEquivalent->deviationFromCentered,
                    'chords'                => implode(',', $transposedSong->transpositionEasierNotEquivalent->chords),
                ];
            }

            $testResult[$transposedSong->song->idSong]['peopleCompatibleStatus'] = $transposedSong->getPeopleCompatibleStatus();

            if (($peopleCompatibleTransposition = $transposedSong->getPeopleCompatible()) !== null) {
                $testResult[$transposedSong->song->idSong]['peopleCompatible'] = [
                    'offset'                => $peopleCompatibleTransposition->offset,
                    'lowestNote'            => $peopleCompatibleTransposition->range->lowest,
                    'highestNote'           => $peopleCompatibleTransposition->range->highest,
                    'score'                 => $peopleCompatibleTransposition->score,
                    'capo'                  => $peopleCompatibleTransposition->getCapo(),
                    'deviationFromCentered' => $peopleCompatibleTransposition->deviationFromCentered,
                    'chords'                => implode(',', $peopleCompatibleTransposition->chords),
                    'peopleLowestNote'      => $peopleCompatibleTransposition->peopleRange->lowest,
                    'peopleHighestNote'     => $peopleCompatibleTransposition->peopleRange->highest,
                ];
            }
        }

        return $testResult;
    }

    private function diffTestResults($actual, $expected)
    {
        $scalarProperties = ['songLowestNote', 'songHighestNote', 'peopleCompatibleStatus'];
        $arrayProperties = ['centered1', 'centered2', 'notEquivalent', 'peopleCompatible'];

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

        if ($transpositionsDiff !== []) {
            $diff = $diff !== []
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
            unset($value);
            if (is_array($diff)) {
                $diff = array_merge($diff, $missingProperties);
            }
        }

        return $diff;
    }
}
