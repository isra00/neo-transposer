<?php

namespace NeoTransposer;

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
	public static function getUserFromDb($email)
	{
		$q = mysql_query("SELECT * FROM user WHERE email LIKE '" . mysql_escape_string($email) . "'");

		$user = null;

		if ($userdata = mysql_fetch_assoc($q))
		{
			$user = new User(
				$userdata['email'],
				$userdata['id_user'],
				$userdata['lowest_note'],
				$userdata['highest_note'],
				$userdata['id_book'],
				$userdata['chord_printer']
			);
		}

		return $user;
	}

	public function persist()
	{
		/** @todo Hacerlo en una sola consulta, con replace or insert */

		if ($this->id_user)
		{
			$q = mysql_query("UPDATE user SET "
				. "lowest_note = '" . mysql_escape_string($this->lowest_note) . "', "
				. "highest_note = '" . mysql_escape_string($this->highest_note) . "', "
				. "id_book = '" . mysql_escape_string($this->id_book) . "', "
				. "chord_printer = '" . mysql_escape_string($this->chord_printer) . "' "
				. "WHERE id_user = '" . intval($this->id_user) . "'");
			
			return mysql_affected_rows();
		} else {
			$q = mysql_query("INSERT INTO user (email, lowest_note, highest_note, id_book, chord_printer)"
				. " VALUES ("
				. "'" . mysql_escape_string($this->email) . "',"
				. "'" . mysql_escape_string($this->lowest_note) . "',"
				. "'" . mysql_escape_string($this->highest_note) . "',"
				. "'" . mysql_escape_string($this->id_book) . "',"
				. "'" . mysql_escape_string($this->chord_printer) . "')");

			$this->id_user = mysql_insert_id();
		}
	}

	public function isRedirectionNeeded()
	{
		if (empty($this->id_user))
		{
			if (basename($_SERVER['SCRIPT_NAME']) == 'login.php')
			{
				return;
			}
			else
			{
				return 'login';
			}
		}

		if (empty($this->lowest_note))
		{
			if (false === array_search(basename($_SERVER['SCRIPT_NAME']), array('wizard.php', 'set_session.php')))
			{
				return 'wizard';
			}
		}
	}
}