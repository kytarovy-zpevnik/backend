<?php

namespace App\Model\Service;

use App\DuplicateEmailException;
use App\DuplicateUsernameException;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Entity\UserSettings;
use App\SecurityException;
use Kdyby\Doctrine\DuplicateEntryException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Tools\NonLockingUniqueInserter;
use Nette\Object;
use Nette\Security\Passwords;

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

	/** @var NonLockingUniqueInserter */
	private $inserter;

	/**
	 * @param int $hashingCost
	 * @param EntityManager $em
	 */
	public function __construct($hashingCost, EntityManager $em)
	{
		$this->hashingCost = $hashingCost;
		$this->em          = $em;
		$this->inserter    = new NonLockingUniqueInserter($em);
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
		$user->passwordHash = $this->getPasswordHash($password);
		$user->role         = $role;
        $user->settings     = new UserSettings();
        $this->inserter->persist($user->settings);

		$user = $this->inserter->persist($user); // reassign needed!

		if (!$user) {
			if ($this->em->getDao(User::getClassName())->findOneBy(['username' => $username])) {
				throw self::duplicateUsername();
			}

			if($this->em->getDao(User::getClassName())->findOneBy(['email' => $email])) {
				throw self::duplicateEmail();
			}
		}
		return $user;
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public function getPasswordHash($password)
	{
		return Passwords::hash($password, ['cost' => $this->hashingCost]);
	}

	/**
	 * @param string $passwordHash
	 * @param string $password
	 * @return bool
	 */
	public function verifyPasswordHash($passwordHash, $password)
	{
		if (!Passwords::verify($password, $passwordHash)) {
			return FALSE;
		}

		if (Passwords::needsRehash($passwordHash, ['cost' => $this->hashingCost])) {
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

	/**
	 * @return DuplicateUsernameException
	 */
	private static function duplicateUsername()
	{
		return new DuplicateUsernameException('Duplicated username.');
	}

	/**
	 * @return DuplicateEmailException
	 */
	private static function duplicateEmail()
	{
		return new DuplicateEmailException('Duplicated email.');
	}

}
