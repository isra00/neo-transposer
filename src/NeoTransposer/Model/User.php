<?php

namespace NeoTransposer\Model;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Persistence\UserPersistence;
use \NeoTransposer\Model\NotesNotation;
use \Symfony\Component\Translation\Translator;

/**
 * Represents a user
 */
class User
{
	public $id_user;
	public $email;
	public $lowest_note;
	public $highest_note;
	public $id_book;
	public $wizard_step1;
	public $wizard_lowest_attempts = 0;
	public $wizard_highest_attempts = 0;

	/**
	 * Simple constructor. Use UserPersistence::fetchUserFromEmail() to create from DB.
	 * 
	 * @param string 	$email         	User email
	 * @param int 		$id_user       	User ID
	 * @param string 	$lowest_note   	User lowest note
	 * @param string 	$highest_note  	User highest note
	 * @param int 		$id_book       	Book
	 * @param int 		wizard_step1	Option checked in Wizard First Step
	 * @param string 	wizard_lowest_attempts No. of attempts in Wizard Lowest note.
	 * @param string 	wizard_highest_attempts No. of attempts in Wizard Lowest note.
	 */
	public function __construct($email=null, $id_user=null, $lowest_note=null, $highest_note=null, $id_book=null, $wizard_step1=null, $wizard_lowest_attempts=null, $wizard_highest_attempts=null)
	{
 		$this->id_user = $id_user;
		$this->email = $email;
		$this->lowest_note = $lowest_note;
		$this->highest_note = $highest_note;
		$this->id_book = $id_book;
		$this->wizard_step1 = $wizard_step1;
		$this->wizard_lowest_attempts = $wizard_lowest_attempts;
		$this->wizard_highest_attempts = $wizard_highest_attempts;
	}

	/**
	 * Create or update the user in the database.
	 * 
	 * @param  \Doctrine\DBAL\Connection $db A DB connection.
	 * @param  Symfony\Component\HttpFoundation\Request $request The Request, for fetching the client IP.
	 * @return integer The user ID, if it was not set.
	 */
	public function persist(\Doctrine\DBAL\Connection $db, Request $request)
	{
		UserPersistence::persist($this, $db, $request);
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

		if (empty($this->lowest_note))
		{
			$exempt = array(
				'user_settings', 
				'user_voice', 
				'set_user_data', 
				'wizard_step1', 
				'wizard_empiric_lowest', 
				'wizard_empiric_highest', 
				'wizard_finish'
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
	function getVoiceAsString(Translator $trans, $notation='american')
	{
		return NotesNotation::getVoiceRangeAsString($trans, $notation, $this->lowest_note, $this->highest_note);
	}
}