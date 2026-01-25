<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Illuminate\Foundation\Testing\TestCase;

class TranspositionFactoryTest extends TestCase
{
    public function testCreateTransposition()
    {
        $chordsScoreConfig = config('nt.chord_scores');

        $expected = new Transposition(
            $chordsScoreConfig,
            ['Em'],
            1,
            true,
            2,
            new NotesRange('from', 'to'),
            3,
            new NotesRange('pfrom', 'pto')
        );

        $sut = new TranspositionFactory();

        $actual = $sut->createTransposition(
            ['Em'],
            1,
            true,
            2,
            new NotesRange('from', 'to'),
            3,
            new NotesRange('pfrom', 'pto')
        );
        $this->assertEquals($expected, $actual);
    }
}
