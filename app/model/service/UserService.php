<?php

namespace App\Model\Service;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\SecurityException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 * User service.
 * @author Tomáš Markacz
 */
class UserService extends Object
{

	/** @var int */
	private $hashingCost;

	/** @var EntityManager */
	private $em;

	/**
	 * @param int $hashingCost
	 * @param EntityManager $em
	 */
	public function __construct($hashingCost, EntityManager $em)
	{
		$this->hashingCost = $hashingCost;
		$this->em          = $em;
	}

	/**
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @param Role $role
	 * @return User
	 */
    public function create($username, $email, $password, Role $role)
	{
		$user = new User();

		$user->username     = $username;
		$user->email        = $email;
		$user->passwordHash = $this->getPasswordHash($password, $email);
		$user->role         = $role;

		$this->em->persist($user);

		return $user;
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public function getPasswordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->hashingCost]);
	}

	/**
	 * @param string $passwordHash
	 * @param string $password
	 * @return bool
	 */
	public function verifyPasswordHash($passwordHash, $password)
	{
		if (!password_verify($password, $passwordHash)) {
			return FALSE;
		}

		if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT, ['cost' => $this->hashingCost])) {
			throw self::passwordNeedsRehash();
		}

		return TRUE;
	}

	/**
	 * @return SecurityException
	 */
	private static function passwordNeedsRehash()
	{
		return new SecurityException('Password needs rehash.');
	}

}
