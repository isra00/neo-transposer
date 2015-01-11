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
	 * Factory
	 * 
	 * @param  [type] $email [description]
	 * @return [type]        [description]
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
	 * @return integer The user ID, if it was not set.
	 */
	public function persist(\Doctrine\DBAL\Connection $db)
	{
		/** @todo Hacerlo en una sola consulta, con replace or insert */

		if ($this->id_user)
		{
			return $db->update('user',
				array(
					'lowest_note' => $this->lowest_note,
					'highest_note' => $this->highest_note,
					'id_book' => $this->id_book,
					'chord_printer' => $this->chord_printer,
				), array('id_user' => (int) $this->id_user)
			);
		}

		$db->insert('user', array(
			'email' => $this->email,
			'lowest_note' => $this->lowest_note,
			'highest_note' => $this->highest_note,
			'id_book' => $this->id_book,
			'chord_printer' => $this->chord_printer
		));

		return $this->id_user = $db->lastInsertId();
	}

	public function isRedirectionNeeded(Request $request)
	{
		$here = $request->attributes->get('_route');

		//Login has its own redirection logic.
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
			if (false === array_search($here, array('user_settings', 'set_user_data')))
			{
				return 'user_settings';
			}
		}
	}

	public function isLoggedIn()
	{
		return !empty($this->id_user);
	}

	/**
	 * Format a numbered note as note + number of octaves above the 1st octave.
	 * 
	 * @param  string $note A numbered note.
	 * @return string Formatted string.
	 *
	 * @todo  Unir este método y el siguiente y pasarlo a User::getVoiceAsString();
	 */
	function getVoiceAsString()
	{
		preg_match('/([ABCDEFG]#?b?)([0-9])/', $this->lowest_note, $match);
		$lowest_note = $match[1];

		preg_match('/([ABCDEFG]#?b?)([0-9])/', $this->highest_note, $match);
		$note = $match[1];
		$octave = intval($match[2]);
		$octave = $octave - 1;
		return "$lowest_note &rarr; $note +$octave oct";
	}
}