<?php

namespace App\Model\Entity;

use DateTime;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $name
 * @property DateTime $created
 * @property DateTime $modified
 * @property bool $archived
 * @property string $public
 * @property string $note
 * @property User $owner
 * @property User[] $viewers
 * @property User[] $editors
 * @property SongbookRating[] $songbookRatings
 * @property SongbookComment[] $songbookComments
 * @property Song[] $songs
 *
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
    protected $created;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modified;

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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $note;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="songbooks")
     */
    protected $owner;

    /**
     * @var User[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedNotEditableSongbooks")
     * @ORM\JoinTable(name="viewers_songbooks")
     */
    protected $viewers;

    /**
     * @var User[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedEditableSongbooks")
     * @ORM\JoinTable(name="editors_songbooks")
     */
    protected $editors;

    /**
     * @var Song[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\Song", mappedBy="songbooks")
     */
    protected $songs;

    /**
     * @var SongbookRating[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookRating", mappedBy="songbook")
     */
    protected $songbookRatings;

    /**
     * @var SongbookComment[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookComment", mappedBy="songbook")
     */
    protected $songbookComments;

    /**
     * @var SongbookTag[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookTag", mappedBy="songbook")
     */
    protected $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Removes all tags.
     */
    public function clearTags()
    {
        $this->tags->clear();
    }
}
