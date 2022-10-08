<?php

namespace NeoTransposer\Domain\ValueObject;

use NeoTransposer\Domain\Exception\SongDataException;

/** @todo PHP8: implements Stringable */
/** @todo PHP8.1 Make it immutable (readonly properties) */
final class Chord
{
    private $fundamental;
    private $attributes;

    private const REGEX_FUNDAMENTAL = '([ABCDEFG]#?b?)';
    private const REGEX_ATTRIBUTES = '([mM45679]*|dim)';

    /**
     * @throws SongDataException
     */
    public function __construct(string $fundamental, string $attributes = null)
    {
        $this->ensureFundamentalIsValid($fundamental);
        $this->ensureAttributesIsValid($attributes);

        $this->fundamental = $fundamental;
        $this->attributes  = $attributes;
    }

    /**
     * @throws SongDataException
     */
    public static function fromString(string $name): Chord
    {
        $regexp = '/^' . self::REGEX_FUNDAMENTAL . self::REGEX_ATTRIBUTES . '$/';
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

    private function ensureFundamentalIsValid(string $fundamental): void
    {
        if (0 === preg_match('/^' . self::REGEX_FUNDAMENTAL . '$/', $fundamental))
        {
            throw new SongDataException("Invalid chord fundamental");
        }
    }

    private function ensureAttributesIsValid(?string $attributes): void
    {
        if (0 === preg_match('/^' . self::REGEX_ATTRIBUTES . '$/', (string) $attributes))
        {
            throw new SongDataException("Invalid chord attributes");
        }
    }

    public function fundamental(): string
    {
        return $this->fundamental;
    }

    public function attributes(): ?string
    {
        return $this->attributes;
    }
}
