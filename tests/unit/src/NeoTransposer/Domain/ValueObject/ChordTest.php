<?php

namespace NeoTransposer\Tests\Domain\ValueObject;

use NeoTransposer\Domain\Exception\SongDataException;
use NeoTransposer\Domain\ValueObject\Chord;
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
