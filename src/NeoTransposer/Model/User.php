<?php

namespace NeoTransposer\Model;

use NeoTransposer\Model\NotesNotation;
use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Persistence\UserPersistence;
use \Symfony\Component\Translation\TranslatorInterface;
use \Doctrine\DBAL\Connection;

/**
 * Represents a user
 */
class User
{
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
	public $feedbacksReported = 0;
	public $firstTime = false;

	/**
	 * Simple constructor. Use UserPersistence::fetchUserFromEmail() to create from DB.
	 *
	 * @param string 		$email         			User email
	 * @param int 			$id_user       			User ID
	 * @param NotesRange|null 	$range  			    User highest note
	 * @param int 			$id_book       			Book
	 * @param int 			wizard_step1			Option checked in Wizard First Step
	 * @param int 			wizard_lowest_attempts 	No. of attempts in Wizard Lowest note.
	 * @param int 			wizard_highest_attempts No. of attempts in Wizard Lowest note.
	 * @param int 			feedbacksReported 		No. of feedback reports sent by the user
	 */
	public function __construct($email=null, $id_user=null, NotesRange $range=null, $id_book=null, $wizard_step1=null, $wizard_lowest_attempts=null, $wizard_highest_attempts=null, $feedbacksReported=0)
	{
 		$this->id_user 		= $id_user;
		$this->email 		= $email;
		$this->range 		= $range;
		$this->id_book 		= $id_book;
		$this->wizard_step1 = $wizard_step1;
		$this->wizard_lowest_attempts = $wizard_lowest_attempts;
		$this->wizard_highest_attempts = $wizard_highest_attempts;
		$this->feedbacksReported = $feedbacksReported;
	}

	/**
	 * Create or update the user in the database.
	 * 
	 * @param Connection $db A DB connection.
	 * @param  string|null $registerIp The IP address with which the user registered.
     * @todo Refactor: Sacar esto de aquí, pues estaríamos haciendo ActiveRecord y queremos data mapper.
	 */
	public function persist(Connection $db, string $registerIp = null): void
	{
		$userPersistence = new UserPersistence($db);
		$userPersistence->persist($this, $registerIp);
	}

	/**
	 * Update the user in the database with logging the voice range change.
	 * 
	 * @param   Connection $db A DB connection.
	 * @param   string|null $registerIp The IP address with which the user registered.
	 * @return  bool Whether the user previously had a voice range.
     * @todo Refactor: sacar esto de aquí por la misma razón que persist()
	 */
	public function persistWithVoiceChange(Connection $db, $registerIp = null, $method): bool
	{
		$userPersistence = new UserPersistence($db);
		return $userPersistence->persistWithVoiceChange($this, $registerIp, $method);
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
}
