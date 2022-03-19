<?php

namespace NeoTransposer\Model;

class PeopleCompatibleCalculation
{
	const NO_PEOPLE_RANGE_DATA 	= 1;
	const ALREADY_COMPATIBLE 	= 2;
	const WIDER_THAN_SINGER 	= 3;
	const ADJUSTED_WIDER 		= 4;
	const TOO_LOW_FOR_PEOPLE 	= 5;
	const TOO_HIGH_FOR_PEOPLE 	= 6;
	const ADJUSTED_WELL 		= 56; /** @todo Rename. "Within the limits" is more objective than "well" */
	const NOT_ADJUSTED_WIDER 	= 7;

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
