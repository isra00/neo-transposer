<?php

namespace NeoTransposer\Domain\Exception;

/**
 * Exceptions produced by problems, incongruences, etc. in the songs data, like
 * unknown chords, maxNote < minNote, repeated chords...
 */
final class SongDataException extends \Exception
{
	
}
