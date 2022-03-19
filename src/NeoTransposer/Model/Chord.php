<?php

namespace NeoTransposer\Model;

final class Chord implements \Stringable
{
    public $fundamental;
    public $attributes;

    public function __construct(string $fundamental, string $attributes = null)
    {
        $this->fundamental = $fundamental;
        $this->attributes = $attributes;
    }

    public static function fromString(string $name): Chord
    {
        $regexp = '/^([ABCDEFG]#?b?)([mM45679]*|dim)$/';
		preg_match($regexp, $name, $match);

		if (!isset($match[2]))
		{
			throw new \Exception("Chord $name not recognized");
		}

        return new Chord($match[1], $match[2]);
    }

    public function __toString()
    {
        return $this->fundamental . $this->attributes;
    }
}