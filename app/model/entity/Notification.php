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
 * @property DateTime $created
 * @property bool $read
 * @property string $text
 * @property User $user
 * @property Song $song
 * @property Songbook $songbook
 *
 * Notification entity.
 * @author Tomáš Jirásek
 */

class Notification extends BaseEntity
{

    use Identifier;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var bool
     * @ORM\Column(name="`read`", type="boolean")
     */
    protected $read;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $text;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="notifications")
     */
    protected $user;

	/**
	 * @var Song
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song")
	 */
	protected $song;

	/**
	 * @var Songbook
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook")
	 */
	protected $songbook;

	/**
	 */
	public function __construct()
	{
		$this->created = new DateTime;
		$this->read    = FALSE;
	}

}
