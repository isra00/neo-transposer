<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\NotesNotation;
use Tests\TestCase;

class NotesNotationTest extends TestCase
{
    /**
     * @var NotesNotation
     */
    protected $notesNotation;

    public function setUp(): void
    {
        parent::setUp();
        $this->notesNotation = new NotesNotation();
    }

    public function testGetNotation()
    {
        $this->assertEquals('Do', $this->notesNotation->getNotation('C', 'latin'));
    }

    public function testGetVoiceRangeAsString()
    {
        $this->assertEquals(
            'A &rarr; A +1 oct',
            $this->notesNotation->getVoiceRangeAsString('american', 'A1', 'A2')
        );
    }

    public function testGetVoiceRangeAsStringLatinNotation()
    {
        $this->assertEquals(
            'La &rarr; La +1 oct',
            $this->notesNotation->getVoiceRangeAsString('latin', 'A1', 'A2')
        );
    }
}
