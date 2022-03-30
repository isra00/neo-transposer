<?php

namespace NeoTransposer\Model;

use NeoTransposer\Domain\Entity\UserPerformance;
use NeoTransposer\Domain\ValueObject\NotesRange;
use Symfony\Component\HttpFoundation\Request;
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

	/**
	 * @type NotesRange
	 */
	public $range;

	public $id_book;
	public $wizard_step1;
	public $wizard_lowest_attempts = 0;
	public $wizard_highest_attempts = 0;

    /** @deprecated */
	public $feedbacksReported = 0;

    /** @var UserPerformance */
    public $performance;

	public $firstTime = false;

	/**
	 * @param string 		$email         			User email
	 * @param int 			$id_user       			User ID
	 * @param NotesRange|null 	$range  			    User highest note
	 * @param int 			$id_book       			Book
	 * @param int 			wizard_step1			Option checked in Wizard First Step
	 * @param int 			wizard_lowest_attempts 	No. of attempts in Wizard Lowest note.
	 * @param int 			wizard_highest_attempts No. of attempts in Wizard Lowest note.
	 * @param int 			feedbacksReported 		No. of feedback reports sent by the user
	 */
	public function __construct($email=null, $id_user=null, NotesRange $range=null, $id_book=null, $wizard_step1=null, $wizard_lowest_attempts=null, $wizard_highest_attempts=null, $performance=null)
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
	 * Redirections depending on the state of the user (not logged in/no 
	 * voice range defined).
	 * 
	 * @param  Request      $request    The HttpFoundation Request, to know the current route.
	 * @return string|null  Address for redirection, if needed.
     *
     * @todo Refactor: el nombre hace esperar un return type boolean.
     *       Esto rompe Single Responsibility, puesto que trabaja con la request.
     *       Más bien, otra clase (LoginFlow o algo así) debería preguntar a esta
     *       si isLoggedIn() y si hasRange() y decidir la ruta de redirección en base a eso.
	 */
	public function isRedirectionNeeded(Request $request)
	{
		$here = $request->attributes->get('_route');

		//Login page has its own redirection logic.
		if ($here == 'login')
		{
			return;
		}

		if (empty($this->id_user))
		{
			return 'login';
		}

		if (!$this->hasRange())
		{
			$exempt = array(
				'user_settings', 
				'user_voice', 
				'set_user_data', 
				'wizard_step1', 
				'wizard_empiric_lowest', 
				'wizard_empiric_highest'
			);
			
			if (!in_array($here, $exempt))
			{
				return 'user_voice';
			}
		}
	}

    /**
     * Whether the user has a defined voice range.
     * @return bool
     */
    public function hasRange(): bool
    {
        return !empty($this->range->lowest);
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
     *
     * @todo Inversión de dependencia: no tiene sentido recibir TranslatorInterface pero instanciar NotesNotation.
	 */
	public function getVoiceAsString(TranslatorInterface $trans, string $notation='american') : string
	{
        $notesNotation = new NotesNotation;
		return $notesNotation->getVoiceRangeAsString($trans, $notation, $this->range->lowest, $this->range->highest);
	}

    public function shouldEncourageFeedback(): bool
    {
        return (
            !empty($this->range->lowest)
            && ($this->performance->reports() < 2 || ($this->performance->reports() == 2 && $this->firstTime))
        );
    }
}
