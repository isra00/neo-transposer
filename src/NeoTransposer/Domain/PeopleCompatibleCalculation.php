<?php

namespace NeoTransposer\Domain;

/**
 * This class' sense is that if NO_PEOPLE_RANGE_DATA or ALREADY_COMPATIBLE
 * or NOT_ADJUSTED_WIDER, then $peopleCompatibleTransposition is null, but still
 * the status info is relevant.
 */
class PeopleCompatibleCalculation
{
    /*
     * These numerical values are used as numbers in testAllTranspositions.expected.PeopleCompatible.json.
     * A refactor for using the strings of $statusMsg would have to adapt that JSON as well.
     */
    public const NO_PEOPLE_RANGE_DATA = 1;
    public const ALREADY_COMPATIBLE   = 2;
    public const WIDER_THAN_SINGER    = 3;
    public const ADJUSTED_WIDER       = 4;
    public const TOO_LOW_FOR_PEOPLE   = 5;
    public const TOO_HIGH_FOR_PEOPLE  = 6;
    public const ADJUSTED_WELL        = 56;
    public const NOT_ADJUSTED_WIDER   = 7;

    protected $statusMsg = [
        self::NO_PEOPLE_RANGE_DATA => 'no_people_range_data',
        self::ALREADY_COMPATIBLE   => 'already_compatible',
        self::WIDER_THAN_SINGER    => 'wider_than_singer',
        self::TOO_LOW_FOR_PEOPLE   => 'too_low_for_people',
        self::TOO_HIGH_FOR_PEOPLE  => 'too_high_for_people',
        self::ADJUSTED_WELL        => 'adjusted_well',
        self::ADJUSTED_WIDER       => 'adjusted_wider',
        self::NOT_ADJUSTED_WIDER   => 'not_adjusted_wider',
    ];

    /**
     * @var Transposition
     */
    public $peopleCompatibleTransposition;

    /**
     * One of the constants defined above.
     * 
     * @var int
     */
    public $status;

    public function __construct($status, Transposition $pct = null)
    {
        $this->peopleCompatibleTransposition = $pct;
        $this->status = $status;
    }

    /**
     * Get a developer-friendly message for the status. Only for debugging.
     */
    public function getStatusMsg() : string
    {
        return $this->status ? $this->statusMsg[$this->status] : '(not set)';
    }
}
