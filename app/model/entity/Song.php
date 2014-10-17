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
 * @property string $title
 * @property string $song
 * @property DateTime $created
 * @property DateTime $modified
 * @property string $album
 * @property string $author
 * @property string $originalAuthor
 * @property int $year
 * @property bool $archived
 * @property string $public
 * @property User $owner
 * @property User[] $viewers
 * @property User[] $editors
 * @property BadContent[] $badContents
 * @property SongComments[] $songComments
 * @property SongRating[] $songRatings
 * @property Tag[] $tags
 *
 * Song entity.
 * @author Tomáš Jirásek
 */
class Song extends BaseEntity
{

    use Identifier;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $lyrics;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $chords;

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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $album;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $author;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $originalAuthor;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $year;

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
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="songs")
     */
    protected $owner;

    /**
     * @var User[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedNotEditableSongs")
     * @ORM\JoinTable(name="viewers_songs")
     */
    protected $viewers;

    /**
     * @var User[]
     * @ORM\ManyToMany(targetEntity="App\Model\Entity\User", inversedBy="sharedEditableSongs")
     * @ORM\joinTable(name="editors_songs")
     */
    protected $editors;

    /**
     * @var BadContent[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\BadContent", mappedBy="song")
     */
    protected $badContents;

    /**
     * @var SongComment[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongComment", mappedBy="song")
     */
    protected $songComments;

    /**
     * @var SongRating[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongRating", mappedBy="song")
     */
    protected $songRatings;

    /**
     * @var Tag[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\Tag", mappedBy="song")
     */
    protected $tags;
}
