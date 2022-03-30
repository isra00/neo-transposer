<?php

namespace NeoTransposer\Tests\Model;

use NeoTransposer\Domain\ValueObject\Chord;
use PHPUnit\Framework\TestCase;

class ChordTest extends TestCase
{
    private $sut; /** @todo Specify type (PHP 7.4+) */

    public function testConstructFromStringValid()
    {
        $this->sut = Chord::fromString("Am");
        $this->assertEquals("A", $this->sut->fundamental);
        $this->assertEquals("m", $this->sut->attributes);
    }

    public function testConstructFromStringInvalid()
    {
        $this->expectException(\Exception::class);
        $this->sut = Chord::fromString("hey mama!");
    }

    public function testConstructFromParts()
    {
        $this->sut = new Chord("A", "m");
        $this->assertEquals("A", $this->sut->fundamental);
        $this->assertEquals("m", $this->sut->attributes);
    }
}
