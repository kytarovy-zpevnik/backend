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
 * @property DateTime $created
 * @property int $rating
 * @property User $user
 *
 * Rating entity.
 * @author Tomáš Jirásek
 */

class Rating extends BaseEntity
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
    protected $created;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $rating;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $user;
}
