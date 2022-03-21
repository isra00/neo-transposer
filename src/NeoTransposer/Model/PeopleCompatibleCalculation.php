<?php

namespace NeoTransposer\Model;

/**
 * This class' sense is that if NO_PEOPLE_RANGE_DATA or ALREADY_COMPATIBLE
 * or NOT_ADJUSTED_WIDER, then $peopleCompatibleTransposition is null, but still
 * the status info is relevant.
 */
class PeopleCompatibleCalculation
{
    /** @todo Usar los valores string de $statusMsg aquí y cargarnos getMsg() ?  */
    public const NO_PEOPLE_RANGE_DATA = 1;
    public const ALREADY_COMPATIBLE = 2;
    public const WIDER_THAN_SINGER = 3;
    public const ADJUSTED_WIDER = 4;
    public const TOO_LOW_FOR_PEOPLE = 5;
    public const TOO_HIGH_FOR_PEOPLE = 6;
    public const ADJUSTED_WELL = 56;
    public const NOT_ADJUSTED_WIDER = 7;

	/**
	 * @var PeopleCompatibleTransposition
	 */
	public $peopleCompatibleTransposition;

	/**
	 * One of the constants defined above.
	 * 
	 * @var int
	 */
	public $status;

	public function __construct($status, PeopleCompatibleTransposition $pct = null)
	{
		$this->peopleCompatibleTransposition = $pct;
		$this->status = $status;
	}

	/**
	 * Get a developer-friendly message for the status. Only for debugging.
	 */
	public function getStatusMsg()
	{
		$statusMsg = [
			self::NO_PEOPLE_RANGE_DATA 	=> 'no_people_range_data',
			self::ALREADY_COMPATIBLE 	=> 'already_compatible',
			self::WIDER_THAN_SINGER 	=> 'wider_than_singer',
			self::TOO_LOW_FOR_PEOPLE 	=> 'too_low_for_people',
			self::TOO_HIGH_FOR_PEOPLE 	=> 'too_high_for_people',
			self::ADJUSTED_WELL 		=> 'adjusted_well',
			self::ADJUSTED_WIDER 		=> 'adjusted_wider',
			self::NOT_ADJUSTED_WIDER 	=> 'not_adjusted_wider',
		];

		return $this->status ? $statusMsg[$this->status] : '(not set)';
	}
}
