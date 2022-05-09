<?php

namespace NeoTransposerApp\Tests\Domain\ValueObject;

use NeoTransposerApp\Domain\Exception\SongDataException;
use NeoTransposerApp\Domain\ValueObject\Chord;
use PHPUnit\Framework\TestCase;

class ChordTest extends TestCase
{
    public function testFromParts()
    {
        $sut = new Chord("A", "m");
        $this->assertEquals("Am", $sut->__toString());
    }

    public function testFromString()
    {
        $string = "Am";
        $this->assertEquals($string, Chord::fromString($string)->__toString());
    }

    public function testFromInvalidString()
    {
        $this->expectException(SongDataException::class);
        Chord::fromString("test");
    }
}
