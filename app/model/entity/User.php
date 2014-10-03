<?php

namespace App\Model\Entity;

use DateTime;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $username
 * @property string $email
 * @property string $passwordHash
 * @property DateTime $lastLogin
 * @property Role $role
 *
 * User entity.
 * @author Tomáš Markacz
 */
class User extends BaseEntity
{

	use Identifier;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $username;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $email;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $passwordHash;

	/**
	 * @var DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $lastLogin;

	/**
	 * @var Role
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\Role")
	 */
	protected $role;

}
