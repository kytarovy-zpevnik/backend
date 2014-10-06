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
 * @property DateTime $createdOn
 * @property bool $read
 * @property int $type
 * @property User $user
 *
 * Notification entity.
 * @author Tomáš Jirásek
 */

class Notification extends BaseEntity
{

    use Identifier;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdOn;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $read;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $type;

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
}
