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
 * @property DateTime $createdOn
 *
 * SongTag entity.
 * @author Tomáš Jirásek
 */

class SongTag extends BaseEntity
{

    use Identifier;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdOn;

    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song")
     */
    protected $song;

    /**
     * @var Tag
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Tag")
     */
    protected $tag;
}
