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
 * @property bool $editable
 *
 * Sharing entity.
 * @author Jiří Mantlík
 */

class Sharing extends BaseEntity
{

    use Identifier;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $user;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $editable;


}
