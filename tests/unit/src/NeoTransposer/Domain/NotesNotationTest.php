<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\NotesNotation;

class NotesNotationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NotesNotation
     */
    protected $notesNotation;

    public function setUp(): void
    {
        $this->notesNotation = new NotesNotation();
    }

    public function testGetNotation()
    {
        $this->assertEquals('Do', $this->notesNotation->getNotation('C', 'latin'));
    }

    public function testGetVoiceRangeAsString()
    {
        $transMock = $this->createMock(\Symfony\Component\Translation\Translator::class);

        $transMock->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oct'))
            ->willReturn('octave');

        $this->assertEquals(
            'A &rarr; A +1 octave',
            $this->notesNotation->getVoiceRangeAsString($transMock, 'american', 'A1', 'A2')
        );
    }

    public function testGetVoiceRangeAsStringLatinNotation()
    {
        $transMock = $this->createMock(\Symfony\Component\Translation\Translator::class);

        $transMock->expects($this->once())
            ->method('trans')
            ->with($this->equalTo('oct'))
            ->willReturn('octave');

        $this->assertEquals(
            'La &rarr; La +1 octave',
            $this->notesNotation->getVoiceRangeAsString($transMock, 'latin', 'A1', 'A2')
        );
    }
}
