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
 * @property string $name
 * @property DateTime $createdOn
 * @property DateTime $modifiedOn
 * @property bool $archived
 * @property string $public
 *
 * Songbook entity.
 * @author Tomáš Jirásek
 */
class Songbook extends BaseEntity
{

    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdOn;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modifiedOn;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $archived;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $public;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="songbooks")
     */
    protected $owner;

    /**
     * @var User
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedNotEditableSongbooks")
     * @ORM\JoinTable(name="viewers_songbooks")
     */
    protected $viewers;

    /**
     * @var User
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedEditableSongbooks")
     * @ORM\JoinTable(name="editors_songbooks")
     */
    protected $editors;

    /**
     * @var SongbookComment[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookComment", mappedBy="songbook")
     */
    protected $songbookComments;

    /**
     * @var SongbookRating[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookRating", mappedBy="songbook")
     */
    protected $songbookRatings;
}
