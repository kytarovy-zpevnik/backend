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
 * @property string $token
 * @property string $authenticationToken
 * @property User $user
 * @property DateTime $create
 * @property DateTime $expiration
 * @property bool $longLife
 *
 * @todo Fill desc.
 * @author Tomáš Markacz
 */
class Session extends BaseEntity
{

	use Identifier;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	protected $token;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
	 */
	protected $user;

	/**
	 * @var DateTime
	 * @ORM\Column(type="datetime")
	 */
	protected $created;

	/**
	 * @var DateTime
	 * @ORM\Column(type="datetime")
	 */
	protected $expiration;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	protected $longLife;

}
