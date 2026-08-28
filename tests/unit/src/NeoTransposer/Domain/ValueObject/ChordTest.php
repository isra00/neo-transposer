<?php

namespace NeoTransposer\Tests\Domain\ValueObject;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\Exception\SongDataException;
use NeoTransposer\Domain\ValueObject\Chord;

class ChordTest extends TestCase
{
    public function test_from_parts()
    {
        $sut = new Chord('A', 'm');
        $this->assertEquals('Am', $sut->__toString());
    }

    public function test_from_string()
    {
        $string = 'Am';
        $this->assertEquals($string, Chord::fromString($string)->__toString());
    }

    public function test_from_invalid_string()
    {
        $this->expectException(SongDataException::class);
        Chord::fromString('test');
    }
}
