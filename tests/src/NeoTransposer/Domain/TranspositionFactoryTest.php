<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\NotesRange;
use PHPUnit\Framework\TestCase;
use Silex\Application;

class TranspositionFactoryTest extends TestCase
{
    public function builderApplication(array $dcContents): Application
    {
        return new Application($dcContents);
    }

    public function testGetTransposition()
    {
        $mockApp = $this->builderApplication([
            'neoconfig' => [
                'chord_scores' => [
                    'chords' => [
                        'Em' => 1,
                    ]
                ]
            ]
        ]);

        $expected = new Transposition(
            $mockApp, ['Em'],
            1,
            true,
            2,
            new NotesRange('from', 'to'),
            3,
            new NotesRange('pfrom', 'pto')
        );

        $sut = new TranspositionFactory($mockApp);

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