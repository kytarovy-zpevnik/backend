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
 * @property string $wish
 * @property bool $meet
 * @property DateTime $created
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
    protected $wish;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $meet;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="wishes")
     */
    protected $user;
}
