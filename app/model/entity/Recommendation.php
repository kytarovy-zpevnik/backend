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
 * @property User $recommendFrom
 * @property User $recommendTo
 *
 * Recommendation entity.
 * @author Tomáš Jirásek
 */

class Recommendation extends BaseEntity
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
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $recommendFrom;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="myRecommendations")
     */
    protected $recommendTo;
}
