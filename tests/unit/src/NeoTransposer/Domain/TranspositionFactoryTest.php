<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\NotesRange;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\Translation\Translator;

class TranspositionFactoryTest extends TestCase
{
    public function buildApplication($chordsScoreConfig, $translator): Application
    {
        return new Application([
            'neoconfig' => [
                'chord_scores' => $chordsScoreConfig
            ],
            'translator' => $translator
        ]);
    }

    public function testCreateTransposition()
    {
        $chordsScoreConfig = ['chords' => ['Em' => 1]];
        $translator = $this->createStub(Translator::class);

        $expected = new Transposition(
            $chordsScoreConfig,
            $translator,
            ['Em'],
            1,
            true,
            2,
            new NotesRange('from', 'to'),
            3,
            new NotesRange('pfrom', 'pto')
        );

        $sut = new TranspositionFactory($this->buildApplication($chordsScoreConfig, $translator));

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