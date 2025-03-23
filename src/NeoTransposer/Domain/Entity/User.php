<?php

namespace NeoTransposer\Domain\Entity;

use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Domain\ValueObject\UserPerformance;

/**
 * Represents a user
 */
class User
{
    //These are stored in MySQL as log_voice_range.method
	final public const METHOD_WIZARD  = 'wizard';
	final public const METHOD_MANUAL  = 'manual';
	final public const METHOD_UNHAPPY = 'auto_unhappy';

	public $firstTime = false;

    // For Laravel Auth.
    protected $rememberToken;
    public $timestamps = false;

    /**
     * @param string|null          $email                   User email
     * @param null                 $id_user                 User ID
     * @param NotesRange|null      $range                   User highest note
     * @param null                 $id_book                 Book
     * @param string|null          $wizard_step1            Option checked in Wizard First Step
     * @param int|null             $wizard_lowest_attempts  No. of attempts in Wizard Lowest note.
     * @param int|null             $wizard_highest_attempts No. of attempts in Wizard Lowest note.
     */
    public function __construct(
        public ?string $email = null,
        public $id_user = null,
        public ?NotesRange $range = null,
        public $id_book = null,
        public ?string $wizard_step1 = null,
        public ?int $wizard_lowest_attempts = null,
        public ?int $wizard_highest_attempts = null,
        public ?UserPerformance $performance = null
    ) {
    }

    public function setPerformance(UserPerformance $performance): void
    {
        $this->performance = $performance;
    }

    /**
     * Whether the user has a defined voice range.
     */
    public function hasRange(): bool
    {
        return !empty($this->range->lowest);
    }

    /**
     * Check if user is logged in or an anonymous user
     */
    public function isLoggedIn(): bool
    {
        return !empty($this->id_user);
    }

	/**
	 * Format the voice of the User as lowest_note - highest note +x octaves
	 *
     * @param   string $notation The notation (american/latin).
	 * @return  string 				Formatted string.
	 */
	public function getVoiceAsString(NotesNotation $notesNotation, string $notation='american') : string
	{
		return $notesNotation->getVoiceRangeAsString($notation, $this->range->lowest, $this->range->highest);
	}

    public function shouldEncourageFeedback(): bool
    {
        return (
            !empty($this->range->lowest)
            && ($this->performance->reports() < 2 || ($this->performance->reports() == 2 && $this->firstTime))
        );
    }
}
