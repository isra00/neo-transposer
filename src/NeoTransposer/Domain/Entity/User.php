<?php

namespace NeoTransposer\Domain\Entity;

use NeoTransposer\Domain\NotesNotation;
use NeoTransposer\Domain\ValueObject\NotesRange;
use NeoTransposer\Domain\ValueObject\UserPerformance;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Represents a user
 */
class User
{
    //These are stored in MySQL as log_voice_range.method
	public const METHOD_WIZARD  = 'wizard';
	public const METHOD_MANUAL  = 'manual';
	public const METHOD_UNHAPPY = 'auto_unhappy';

	public $id_user;
	public $email;

	/** @var NotesRange */
	public $range;

	public $id_book;
	public $wizard_step1;
	public $wizard_lowest_attempts = 0;
	public $wizard_highest_attempts = 0;

    /** @var UserPerformance */
    public $performance;

	public $firstTime = false;

    /**
     * @param string|null          $email                   User email
     * @param null                 $id_user                 User ID
     * @param NotesRange|null      $range                   User highest note
     * @param null                 $id_book                 Book
     * @param string|null          $wizard_step1            Option checked in Wizard First Step
     * @param int|null             $wizard_lowest_attempts  No. of attempts in Wizard Lowest note.
     * @param int|null             $wizard_highest_attempts No. of attempts in Wizard Lowest note.
     * @param UserPerformance|null $performance
     */
	public function __construct(string $email=null, $id_user=null, NotesRange $range=null, $id_book=null, string $wizard_step1=null, int $wizard_lowest_attempts=null, int $wizard_highest_attempts=null, UserPerformance $performance=null)
	{
 		$this->id_user 		           = $id_user;
		$this->email 		           = $email;
		$this->range 		           = $range;
		$this->id_book 		           = $id_book;
		$this->wizard_step1            = $wizard_step1;
		$this->wizard_lowest_attempts  = $wizard_lowest_attempts;
		$this->wizard_highest_attempts = $wizard_highest_attempts;
		$this->performance             = $performance;
	}

    public function setPerformance(UserPerformance $performance): void
    {
        $this->performance = $performance;
    }

    /**
     * Whether the user has a defined voice range.
     * @return bool
     */
    public function hasRange(): bool
    {
        return !empty($this->range);
    }

	/**
	 * Check if user is logged in or an anonymous user
	 *
	 * @return bool
	 */
	public function isLoggedIn(): bool
    {
		return !empty($this->id_user);
	}

	/**
	 * Format the voice of the User as lowest_note - highest note +x octaves
	 *
	 * @param   TranslatorInterface $trans 		The Translator service.
	 * @param   string              $notation 	The notation (american/latin).
	 * @return  string 				Formatted string.
	 */
	public function getVoiceAsString(TranslatorInterface $trans, NotesNotation $notesNotation, string $notation='american') : string
	{
		return $notesNotation->getVoiceRangeAsString($trans, $this->range->lowest(), $this->range->highest(), $notation);
	}

    public function shouldEncourageFeedback(): bool
    {
        return (
            !empty($this->range->lowest())
            && ($this->performance->reports() < 2 || ($this->performance->reports() == 2 && $this->firstTime))
        );
    }
}
