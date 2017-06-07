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

	public function __construct($status, PeopleCompatibleTransposition $pct=null)
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
			self::NO_PEOPLE_RANGE_DATA 	=> 'No people range data for this song',
			self::ALREADY_COMPATIBLE 	=> 'Centered already falls on people\'s range :-)',
			self::WIDER_THAN_SINGER 	=> 'Not adjusted bc song\'s range >= singer\'s range',
			self::TOO_LOW_FOR_PEOPLE 	=> 'Improved, but still too low for the people',
			self::TOO_HIGH_FOR_PEOPLE 	=> 'Improved, but still too high for the people',
			self::ADJUSTED_WELL 		=> 'Adjusted so that it fits well the people!',
			self::ADJUSTED_WIDER 		=> 'Adjusted but still high bc it is wider',
			self::NOT_ADJUSTED_WIDER 	=> 'Wider than people\'s range and already in its bottom',
		];

		return $this->status ? $statusMsg[$this->status] : '(not set)';
	}
}
