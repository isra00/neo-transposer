<?php

namespace NeoTransposer\Tests\Domain;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\NotesCalculator;
use NeoTransposer\Domain\Transposition;
use NeoTransposer\Domain\TranspositionFactory;
use NeoTransposer\Domain\ValueObject\Chord;
use NeoTransposer\Domain\ValueObject\NotesRange;

class TranspositionTest extends TestCase
{
    /**
     * Fixture of the SUT.
     *
     * @var Transposition
     */
    protected $transposition;

    /**
     * An instance of NotesCalculator, needed by some methods.
     *
     * @var NotesCalculator;
     */
    protected $notesCalculator;

    protected $transpositionFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transpositionFactory = new TranspositionFactory();

        $this->transposition = $this->buildTransposition(
            [Chord::fromString('Em'), Chord::fromString('Am'), Chord::fromString('B')],
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->notesCalculator = new NotesCalculator();
    }

    protected function buildTransposition(
        array $chords = [],
        ?int $capo = 0,
        ?bool $asBook = false,
        ?int $offset = 0,
        ?NotesRange $range = null,
        ?int $deviationFromCentered = 0,
        ?NotesRange $peopleRange = null
    ) {
        return $this->transpositionFactory->createTransposition(
            $chords,
            $capo,
            $asBook,
            $offset,
            $range,
            $deviationFromCentered,
            $peopleRange
        );
    }

    public function test_get_with_alternative_chords()
    {
        $this->transposition->setAlternativeChords($this->notesCalculator);
        $this->assertEquals(['Em', 'Am', 'B7'], $this->transposition->chords);

        // If AsBook, alternative chords should not be calculated.
        $chords2 = ['Em', 'Am', 'B'];
        $transposition = $this->buildTransposition($chords2, 0, true, null, null, null, null);
        $transposition->setAlternativeChords($this->notesCalculator);
        $this->assertEquals($chords2, $transposition->chords);
    }

    public function test_calculate_people_range()
    {
        $this->transposition->calculatePeopleRange(
            new NotesRange('A1', 'A2'),
            2,
            $this->notesCalculator
        );

        $this->assertEquals(new NotesRange('B1', 'B2'), $this->transposition->peopleRange);
    }

    public function test_sort_by_ease()
    {
        $transpositionMockA = $this->getMockBuilder(Transposition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transpositionMockB = clone $transpositionMockA;

        $transpositionMockA->score = 10;
        $transpositionMockB->score = 20;

        $this->assertEquals(
            [$transpositionMockA, $transpositionMockB],
            Transposition::sortByEase([$transpositionMockB, $transpositionMockA])
        );
    }

    public function test_sort_by_ease_when_equal_score_prioritize_as_book()
    {
        $transpositionMockA = $this->getMockBuilder(Transposition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transpositionMockB = clone $transpositionMockA;

        $transpositionMockA->score = 10;
        $transpositionMockB->score = 10;

        $transpositionMockA->setAsBook(true);

        $this->assertEquals(
            [$transpositionMockA, $transpositionMockB],
            Transposition::sortByEase([$transpositionMockB, $transpositionMockA])
        );
    }
}
