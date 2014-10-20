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
 * @property Song[] $songs
 * @property Song[] $sharedNotEditableSongs
 * @property Song[] $sharedEditableSongs
 * @property Songbook[] $songbooks
 * @property Songbook[] $sharedNotEditableSongbooks
 * @property Songbook[] $sharedEditableSongbooks
 * @property Wish[] $wishes;
 * @property Ban[] $bans
 * @property Notification[] $notifications
 * @property Recommendation[] $myRecommendations
 * @property PasswordReset $passwordReset
 *
 * User entity.
 * @author Tomáš Markacz, Tomáš Jirásek
 */

class User extends BaseEntity
{

	use Identifier;

	/**
	 * @var string
	 * @ORM\Column(type="string", unique=true)
	 */
	protected $username;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $lastName;

	/**
	 * @var string
	 * @ORM\Column(type="string", unique=true)
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
    protected $sharedNotEditableSongs;

    /**
     * @var Song[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Song", mappedBy="editors")
     */
    protected $sharedEditableSongs;

    /**
     * @var Songbook[]$sharedNotEditableSongs
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="owner")
     */
    protected $songbooks;

    /**
     * @var Songbook[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="viewers")
     */
    protected $sharedNotEditableSongbooks;

    /**
     * @var Songbook[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Songbook", mappedBy="editors")
     */
    protected $sharedEditableSongbooks;

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

    /**
     * @var Recommendation[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Recommendation", mappedBy="recommendTo")
     */
    protected $myRecommendations;

    /**
     * @var PasswordReset
     * @ORM\OneToOne(targetEntity="PasswordReset", mappedBy="user")
     */
    protected $passwordReset;
}
