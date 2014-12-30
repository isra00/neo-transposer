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
}