<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 *
 * @property-read int $id
 * @property string $tag
 * @property bool $public
 * @property User $user
 *
 * Tag entity.
 * @author Tomáš Jirásek
 */

abstract class Tag extends BaseEntity
{
    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tag;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $public;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="tags")
     */
    protected $user;
}
