<?php

namespace App\Domain\ValueObject;

use App\Domain\Exception\SongDataException;

/** @todo PHP8: implements Stringable */
/** @todo Make it immutable */
final class Chord implements \Stringable
{
    public function __construct(
        public string $fundamental,
        public ?string $attributes = null)
    {
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

    public function __toString(): string
    {
        return $this->fundamental . $this->attributes;
    }
}