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
 * @property string $comment
 * @property DateTime $createdOn
 * @property bool $legitimate
 * @property User $user
 *
 * Ban entity.
 * @author Tomáš Jirásek
 */

class Ban extends BaseEntity
{

    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $comment;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdOn;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $legitimate;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="bans")
     */
    protected $user;
}
