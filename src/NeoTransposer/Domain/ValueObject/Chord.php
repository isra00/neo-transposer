<?php

namespace NeoTransposer\Domain\ValueObject;

use NeoTransposer\Domain\Exception\SongDataException;

/** @todo PHP8: implements Stringable */
/** @todo Make it immutable */
final class Chord
{
    public $fundamental;
    public $attributes;

    public function __construct(string $fundamental, string $attributes = null)
    {
        $this->fundamental = $fundamental;
        $this->attributes = $attributes;
    }

    /**
     * @throws SongDataException
     */
    public static function fromString(string $name): Chord
    {
        $regexp = '/^([ABCDEFG]#?b?)([mM45679]*|dim)$/';
		preg_match($regexp, $name, $match);

		if (!isset($match[2]))
		{
			throw new SongDataException("Chord $name not recognized");
		}

        return new Chord($match[1], $match[2]);
    }

    public function __toString()
    {
        return $this->fundamental . $this->attributes;
    }
}