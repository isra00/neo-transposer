<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\AutomaticTransposer;
use NeoTransposer\Domain\AutomaticTransposerFactory;
use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\NotesRange;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\Translation\Translator;

final class TransposedSongTest extends TestCase
{
    protected $sut;

    protected $chordsScoreConfig = [
        'chords' => [
            'Em'    => 1,
            'Am'    => 2,
            'Dm'    => 2,
        ],
        'patterns' => [
            '.?m6'	=> 19,
            '.?#m6'	=> 20,
            '.?7M'	=> 20,
            '.?#7M'	=> 21,
            '.?m9'	=> 24,
            '.?#m9'	=> 25,
            '.?m5'	=> 24,
            '.?#m5'	=> 25,
            '.?dim' => 25,
            '.?#dim' => 26,
        ]
    ];

    protected $printedChordSet = ['E<em>m</em>', 'A<em>m</em>'];

    protected function buildSong(): Song
    {
        $song = Song::fromDbColumns(
            [
                'id_song'             => 'testIdSong',
                'id_book'             => 'testIdBook',
                'page'                => 'testPage',
                'title'               => 'testTitle',
                'lowest_note'         => 'A2',
                'highest_note'        => 'D3',
                'slug'                => 'testSlug',
                'first_chord_is_tone' => 'testFirstChordIsKey',
                'people_lowest_note'  => 'testPeopleLowest',
                'people_highest_note' => 'testPeopleHighest',
                'chord_printer'       => 'testChordPrinter',
                'locale'              => 'testLocale'
            ],
            ["Am", "Dm"]
        );

        $song->originalChordsForPrint = $this->printedChordSet;

        return $song;
    }

    protected function buildTransposition(Application $app): Transposition
    {
        $transposition = (new TranspositionFactory($app))->createTransposition(["Em", "Am"]);
        $transposition->chordsForPrint = $this->printedChordSet;
        return $transposition;
    }

    protected function buildApp(AutomaticTransposer $mockAutomaticTransposer): Application
    {
        $app = new Application();
        $app['neoconfig'] = [
            'chord_scores' => $this->chordsScoreConfig,
            'people_compatible' => true,
            'hide_second_centered_if_not_equivalent' => true,
            'people_range' => ['B1', 'B2'],
        ];

        $app['translator'] = $this->createStub(Translator::class);

        $mockAutomaticTransposerFactory = $this->createMock(AutomaticTransposerFactory::class);
        $mockAutomaticTransposerFactory->method('createAutomaticTransposer')
            ->willReturn($mockAutomaticTransposer);

        $app[AutomaticTransposerFactory::class] = $mockAutomaticTransposerFactory;

        $printedChordSet = $this->printedChordSet;

        $app['factory.ChordPrinter'] = $app->protect(function ($printer) use ($printedChordSet) {
            $mockPrinter = $this->createMock(\NeoTransposer\Domain\ChordPrinter\ChordPrinter::class);
            $mockPrinter
                //->expects($this->once())
                ->method('printChordset')
                //->with($this->getTestInstanceTransposition()->chords)
                ->willReturn($printedChordSet);

            return $mockPrinter;
        });

		return $app;
	}

    public function testTransposeNoForceNoNotEquivalentNotPeopleCompatible(): void
    {
        $mockAutomaticTransposer = $this->createMock(AutomaticTransposer::class);
        $app = $this->buildApp($mockAutomaticTransposer);

        $mockAutomaticTransposer->expects($this->once())
            ->method('getTranspositionsCentered')
            ->willReturn([$this->buildTransposition($app)]);
        $mockAutomaticTransposer->expects($this->once())
            ->method('getEasierNotEquivalent')
            ->willReturn(null);

        $this->sut = new TransposedSong($this->buildSong(), $app);
        $this->sut->transpose(new NotesRange('A1', 'E3'));
        $this->assertEquals([$this->buildTransposition($app)], $this->sut->transpositions);
        $this->assertEquals(null, $this->sut->not_equivalent);

        //Testing prepareForPrint()
        $this->assertEquals($this->printedChordSet, $this->sut->song->originalChordsForPrint);
    }

}
