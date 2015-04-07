<?php

namespace NeoTransposer;

use Symfony\Component\HttpFoundation\Request;

class User
{
	public $id_user;
	public $email;
	public $lowest_note;
	public $highest_note;
	public $id_book;
	public $chord_printer;

	/**
	 * Simple constructor. Check fetchUserFromEmail() to create from DB.
	 * 
	 * @param string 	$email         User email
	 * @param int 		$id_user       User ID
	 * @param string 	$lowest_note   User lowest note
	 * @param string 	$highest_note  User highest note
	 * @param int 		$id_book       Book in user
	 * @param string 	$chord_printer ChordPrinter in user
	 */
	public function __construct($email=null, $id_user=null, $lowest_note=null, $highest_note=null, $id_book=null, $chord_printer=null)
	{
		$this->id_user = $id_user;
		$this->email = $email;
		$this->lowest_note = $lowest_note;
		$this->highest_note = $highest_note;
		$this->id_book = $id_book;
		$this->chord_printer = $chord_printer;
	}

	/**
	 * Factory: get a User object from the DB
	 * 
	 * @param  string 						$email 	User e-mail
	 * @param  \Doctrine\DBAL\Connection 	$db 	Database connection.
	 * @return User        					The User instance for that e-mail.
	 */
	public static function fetchUserFromEmail($email, \Doctrine\DBAL\Connection $db)
	{
		$sql = 'SELECT * FROM user WHERE email LIKE ?';
		
		if ($userdata = $db->fetchAssoc($sql, array($email)))
		{
			return new User(
				$userdata['email'],
				$userdata['id_user'],
				$userdata['lowest_note'],
				$userdata['highest_note'],
				$userdata['id_book'],
				$userdata['chord_printer']
			);
		}
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
		/** @todo Hacerlo en una sola consulta, con replace or insert */

		if ($this->id_user)
		{
			$db->update('user',
				array(
					'lowest_note'	=> $this->lowest_note,
					'highest_note'	=> $this->highest_note,
					'id_book'		=> $this->id_book,
					'chord_printer'	=> $this->chord_printer,
				), array('id_user' => (int) $this->id_user)
			);

			$db->insert('user_edit', array(
				'id_user'		=> $this->id_user,
				'lowest_note'	=> $this->lowest_note,
				'highest_note'	=> $this->highest_note,
				'id_book'		=> $this->id_book,
				'chord_printer'	=> $this->chord_printer,
				'request_uri' 	=> $_SERVER['REQUEST_URI'],
				'referer' 		=> $_SERVER['HTTP_REFERER']
			));
		}

		$db->insert('user', array(
			'email'			=> $this->email,
			'lowest_note'	=> $this->lowest_note,
			'highest_note'	=> $this->highest_note,
			'id_book'		=> $this->id_book,
			'chord_printer'	=> $this->chord_printer,
			'register_ip'	=> $request->getClientIp()
		));

		return $this->id_user = $db->lastInsertId();
	}

	/**
	 * Redirections depending on the state of the user (not logged in, no voice...)
	 * @param  Request $request [description]
	 * @return boolean          [description]
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
	 * @param  \Silex\Translator 	$trans 		The Translator service.
	 * @param  string 				$notation 	The notation (american/latin).
	 * @return string 				Formatted string.
	 */
	function getVoiceAsString(\Silex\Translator $trans, $notation='american')
	{
		$regexp = '/([ABCDEFG]#?b?)([0-9])/';
		
		preg_match($regexp, $this->lowest_note, $match);
		$lowest_note = $match[1];

		preg_match($regexp, $this->highest_note, $match);
		$highest_note = $match[1];

		if ('latin' == $notation)
		{
			$lowest_note = NotesCalculator::getNotation($lowest_note, 'latin');
			$highest_note = NotesCalculator::getNotation($highest_note, 'latin');
		}

		$octave = intval($match[2]);
		$octave = $octave - 1;

		return "$lowest_note &rarr; $highest_note +$octave " . $trans->trans('oct');
	}
}