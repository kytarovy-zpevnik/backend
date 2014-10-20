<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;



/**
 * @ORM\Entity
 * @author peorupav
 *
 * @property-read int $id
 * @property User $user
 * @property DateTime $createdOn
 * @property string $token
 *
 */
class PasswordReset extends BaseEntity {
    use Identifier;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="User", inversedBy="passwordReset")
     */
    protected $user;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $createdOn;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $token;
}