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
 * @property string $firstName
 * @property string $lastName
 * @property string $email
 * @property string $passwordHash
 * @property DateTime $lastLogin
 * @property Role $role
 *
 * User entity.
 * @author Tomáš Markacz, Tomáš Jirásek
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
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $lastName;

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

    /**
     * @var Song[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Song", mappedBy="owner")
     */
    protected $songs;

    /**
     * @var Song[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Song", mappedBy="viewers")
     */
    private $sharedNotEditableSongs;

    /**
     * @var Song[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Song", mappedBy="editors")
     */
    private $sharedEditableSongs;


    /**
     * @var Songbook[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="owner")
     */
    protected $songbooks;

    /**
     * @var Songbook[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="viewers")
     */
    private $sharedNotEditableSongbooks;

    /**
     * @var Songbook[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="editors")
     */
    private $sharedEditableSongbooks;

    /**
     * @var Wish[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Wish", mappedBy="user")
     */
    protected $wishes;

    /**
     * @var Ban[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Ban", mappedBy="user")
     */
    protected $bans;

    /**
     * @var Notification[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Notification", mappedBy="user")
     */
    protected $notifications;
}
