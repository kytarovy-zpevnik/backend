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
 * @property string $name
 * @property string $interpret
 * @property string $note
 * @property DateTime $created
 * @property DateTime $modified
 * @property User $user
 *
 * Wish entity.
 * @author Tomáš Jirásek
 */

class Wish extends BaseEntity
{

    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $interpret;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $note;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="wishes")
     */
    protected $user;
}
