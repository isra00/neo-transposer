<?php

/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace NeoTransposer\Tests\Domain;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\NotesNotation;

class NotesNotationTest extends TestCase
{
    /**
     * @var NotesNotation
     */
    protected $notesNotation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notesNotation = new NotesNotation();
    }

    public function test_get_notation()
    {
        $this->assertEquals('Do', $this->notesNotation->getNotation('C', 'latin'));
    }

    public function test_get_voice_range_as_string()
    {
        $this->assertEquals(
            'A &rarr; A +1 oct',
            $this->notesNotation->getVoiceRangeAsString('american', 'A1', 'A2')
        );
    }

    public function test_get_voice_range_as_string_latin_notation()
    {
        $this->assertEquals(
            'La &rarr; La +1 oct',
            $this->notesNotation->getVoiceRangeAsString('latin', 'A1', 'A2')
        );
    }
}
