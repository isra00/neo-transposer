<?php

namespace NeoTransposer\Model;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Persistence\UserPersistence;
use \NeoTransposer\Model\NotesNotation;
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
	 * @param NotesRange 	$highest_note  			User highest note
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
	 * @param  \Doctrine\DBAL\Connection $db A DB connection.
	 * @param  string $registerIp The IP address with which the user registered.
	 * @return integer The user ID, if it was not set.
	 */
	public function persist(Connection $db, $registerIp = null)
	{
		$userPersistence = new UserPersistence($db);
		$userPersistence->persist($this, $registerIp);
	}

	/**
	 * Update the user in the database with logging the voice range change.
	 * 
	 * @param  \Doctrine\DBAL\Connection $db A DB connection.
	 * @param  string $registerIp The IP address with which the user registered.
	 * @return integer The user ID, if it was not set.
	 */
	public function persistWithVoiceChange(Connection $db, $registerIp = null, $method)
	{
		$userPersistence = new UserPersistence($db);
		return $userPersistence->persistWithVoiceChange($this, $registerIp, $method);
	}

	/**
	 * Redirections depending on the state of the user (not logged in/no 
	 * voice range defined).
	 * 
	 * @param  Request $request The HttpFoundation Request, to know the current route.
	 * @return string          	Address for redirection, if needed.
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

		/** @todo Replace this for $this->hasVoiceMeasured() and use it in Login::post() too */
		if (empty($this->range->lowest))
		{
			$exempt = array(
				'user_settings', 
				'user_voice', 
				'set_user_data', 
				'wizard_step1', 
				'wizard_empiric_lowest', 
				'wizard_empiric_highest'
			);
			
			if (false === array_search($here, $exempt))
			{
				return 'user_voice';
			}
		}
	}

	/**
	 * Check if user is logged in or an anonymous user
	 * 
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return !empty($this->id_user);
	}

	/**
	 * Format the voice of the User as lowest_note - highest note +x octaves
	 * 
	 * @param  Translator 	$trans 		The Translator service.
	 * @param  string 		$notation 	The notation (american/latin).
	 * @return string 					Formatted string.
	 */
	public function getVoiceAsString(TranslatorInterface $trans, $notation='american')
	{
		return NotesNotation::getVoiceRangeAsString($trans, $notation, $this->range->lowest, $this->range->highest);
	}
}
