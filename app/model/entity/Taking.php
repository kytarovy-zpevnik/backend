<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 *
 * @property-read int $id
 * @property User $user
 *
 * Taking entity.
 * @author Jiří Mantlík
 */

class Taking extends BaseEntity
{

    use Identifier;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $user;



}