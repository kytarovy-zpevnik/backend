<?php

namespace App\Model\Entity;

use DateTime;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 *
 * @property-read int $id
 * @property string $comment
 * @property DateTime $created
 * @property DateTime $modified
 * @property User $user
 *
 * Comment entity.
 * @author Tomáš Jirásek
 */

class Comment extends BaseEntity
{

    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $comment;

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
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $user;
}
