<?php

namespace NeoTransposer\Tests\Domain;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\AutomaticTransposer;
use NeoTransposer\Domain\AutomaticTransposerFactory;
use NeoTransposer\Domain\ChordPrinter\ChordPrinter;
use NeoTransposer\Domain\Entity\Song;
use NeoTransposer\Domain\PeopleCompatibleCalculation;
use NeoTransposer\Domain\TransposedSong;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\NotesRange;

final class TransposedSongTest extends TestCase
{
    protected $sut;

    protected $printedChordSet = ['E<em>m</em>', 'A<em>m</em>'];

    protected function buildSong(): Song
    {
        $song = new Song(
            [
                'id_song'             => 'testIdSong',
                'id_book'             => 'testIdBook',
                'page'                => 'testPage',
                'title'               => 'testTitle',
                'lowest_note'         => 'A2',
                'highest_note'        => 'D3',
                'slug'                => 'testSlug',
                'first_chord_is_tone' => 'testFirstChordIsTone',
                'people_lowest_note'  => 'testPeopleLowest',
                'people_highest_note' => 'testPeopleHighest',
                'chord_printer'       => 'testChordPrinter',
                'locale'              => 'testLocale',
                'url'                 => 'testUrl',
            ],
            ['Am', 'Dm']
        );

        $song->originalChordsForPrint = $this->printedChordSet;

        return $song;
    }

    protected function buildTransposition(): Transposition
    {
        $transposition = (new TranspositionFactory())->createTransposition(['Em', 'Am']);
        $transposition->chordsForPrint = $this->printedChordSet;

        return $transposition;
    }

    public function test_transpose_no_force_no_not_equivalent_not_people_compatible(): void
    {
        $mockAutomaticTransposer = $this->createMock(AutomaticTransposer::class);

        $mockAutomaticTransposer->expects($this->once())
            ->method('getTranspositionsCentered')
            ->willReturn([$this->buildTransposition()]);
        $mockAutomaticTransposer->expects($this->once())
            ->method('getEasierNotEquivalent')
            ->willReturn(null);
        $mockAutomaticTransposer->expects($this->once())
            ->method('calculatePeopleCompatible')
            ->willReturn(new PeopleCompatibleCalculation(PeopleCompatibleCalculation::NO_PEOPLE_RANGE_DATA, null));

        $mockAutomaticTransposerFactory = $this->createMock(AutomaticTransposerFactory::class);
        $mockAutomaticTransposerFactory->method('createAutomaticTransposer')
            ->willReturn($mockAutomaticTransposer);

        $this->app->instance(AutomaticTransposerFactory::class, $mockAutomaticTransposerFactory);

        $mockPrinter = $this->createMock(ChordPrinter::class);
        $mockPrinter->method('printChordset')
            ->willReturn($this->printedChordSet);

        $this->app->instance('factory.ChordPrinter', function ($printer) use ($mockPrinter) {
            return $mockPrinter;
        });

        $this->sut = new TransposedSong($this->buildSong());
        $this->sut->transpose(new NotesRange('A1', 'E3'));
        $this->assertEquals([$this->buildTransposition()], $this->sut->transpositions);
        $this->assertEquals(null, $this->sut->not_equivalent);

        // Testing prepareForPrint()
        $this->assertEquals($this->printedChordSet, $this->sut->song->originalChordsForPrint);
    }
}
