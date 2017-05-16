<?php

namespace NeoTransposer\Persistence;

use Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\Model\User;

/**
 * Persistence layer for the User entity.
 */
class UserPersistence
{
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $db;

	/**
	 * @param \Doctrine\DBAL\Connection $db A DBAL connection.
	 */
	public function __construct(\Doctrine\DBAL\Connection $db)
	{
		$this->db = $db;
	}

	/**
	 * Factory: get a User object from the DB
	 * 
	 * @param  string 						$email 	User e-mail
	 * @return User        					The User instance for that e-mail.
	 */
	public function fetchUserFromEmail($email)
	{
		$sql = 'SELECT * FROM user WHERE email LIKE ?';
		
		if ($userdata = $this->db->fetchAssoc($sql, array($email)))
		{
			return new User(
				$userdata['email'],
				$userdata['id_user'],
				$userdata['lowest_note'],
				$userdata['highest_note'],
				$userdata['id_book'],
				$userdata['wizard_step1'],
				$userdata['wizard_lowest_attempts'],
				$userdata['wizard_highest_attempts']
			);
		}
	}

	/**
	 * Create or update the user in the database.
	 * 
	 * @param  NeoTransposer\Model\User 			$user 	The User object to persist.
	 * @param  \Symfony\Component\HttpFoundation\Request $request The Request, for fetching the client IP.
	 * @return integer The user ID, if it was not set.
	 */
	public function persist(User $user, Request $request)
	{
		if ($user->id_user)
		{
			return $this->db->update('user',
				array(
					'lowest_note'	=> $user->lowest_note,
					'highest_note'	=> $user->highest_note,
					'id_book'		=> $user->id_book,
					'wizard_step1' => $user->wizard_step1,
					'wizard_lowest_attempts' => $user->wizard_lowest_attempts,
					'wizard_highest_attempts' => $user->wizard_highest_attempts
				), array('id_user' => (int) $user->id_user)
			);
		}

		$this->db->insert('user', array(
			'email'			=> $user->email,
			'lowest_note'	=> $user->lowest_note,
			'highest_note'	=> $user->highest_note,
			'id_book'		=> $user->id_book,
			'register_ip'	=> $request->getClientIp()
		));

		return $user->id_user = $this->db->lastInsertId();
	}
}
