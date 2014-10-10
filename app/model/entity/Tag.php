<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $tag
 * @property bool $public
 * @property Song $song
 * @property User $user
 *
 * Tag entity.
 * @author Tomáš Jirásek
 */

class Tag extends BaseEntity
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
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="tags")
     */
    protected $song;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User")
     */
    protected $user;
}
