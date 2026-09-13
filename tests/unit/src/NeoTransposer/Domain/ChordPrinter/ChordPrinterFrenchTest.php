<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace NeoTransposer\Tests\Domain\ChordPrinter;

use NeoTransposer\Domain\ChordPrinter\ChordPrinterFrench;
use NeoTransposer\Domain\ValueObject\Chord;
use Illuminate\Foundation\Testing\TestCase;

class ChordPrinterFrenchTest extends TestCase
{
    /**
     * @dataProvider chordsProvider
     */
    public function testPrintChord(string $chord, string $expected)
    {
        $this->assertEquals($expected, (new ChordPrinterFrench())->printChord(Chord::fromString($chord)));
    }

    /**
     * Every chord type present in the song_chord table, plus the fundamentals that
     * transposition can produce out of them.
     */
    public static function chordsProvider(): array
    {
        return [
            //Major
            ['A',     'La'],
            ['G',     'Sol'],
            //Flats: the songbook always spells A# as Bb
            ['A#',    'Si<em>b</em>'],
            ['A#7',   'Si<em>b</em> 7'],
            //Sharps
            ['F#',    'Fa#'],
            ['C#',    'Do#'],
            ['D#',    'Re#'],
            ['G#',    'Sol#'],
            ['F#m',   'Fa# <em>m</em>'],
            //Minor
            ['Am',    'La <em>m</em>'],
            ['Cm',    'Do <em>m</em>'],
            //Seventh, detached from the note
            ['E7',    'Mi 7'],
            ['C#7',   'Do# 7'],
            //Added interval on a minor chord, detached from the "m"
            ['Dm5',   'Re <em>m</em> 5'],
            ['Em6',   'Mi <em>m</em> 6'],
            ['Dm9',   'Re <em>m</em> 9'],
            //Major seventh
            ['F7M',   'Fa <em>maj7</em>'],
            //Diminished
            ['C#dim', 'Do# <em>dim</em>'],
        ];
    }
}
