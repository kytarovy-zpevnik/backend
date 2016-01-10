<?php

namespace App\Model\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $title
 * @property string $chords
 * @property string $lyrics
 * @property DateTime $created
 * @property DateTime $modified
 * @property string $album
 * @property string $author
 * @property string $originalAuthor
 * @property int $year
 * @property bool $archived
 * @property bool $public
 * @property string $note
 * @property User $owner
 * @property User[] $viewers
 * @property User[] $editors
 * @property BadContent[] $badContents
 * @property SongRating[] $songRatings
 * @property SongComment[] $songComments
 * @property Tag[] $tags
 * @property Songbook[] $songbooks
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
    protected $archived = FALSE;

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
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\User", inversedBy="songs")
     */
    protected $owner;

    /**
     * @var SongSongbook[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongSongbook", mappedBy="song")
     */
    protected $songbooks;

    /**
     * @var SongSharing[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongSharing", mappedBy="song")
     */
    protected $songShares;

    /**
     * @var SongTaking[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongTaking", mappedBy="song")
     */
    protected $songTakes;

    /**
     * @var BadContent[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\BadContent", mappedBy="song")
     */
    protected $badContents;

    /**
     * @var SongRating[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongRating", mappedBy="song")
     */
    protected $songRatings;

    /**
     * @var SongComment[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongComment", mappedBy="song")
     */
    protected $songComments;

    /**
     * @var SongTag[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongTag", mappedBy="song")
     */
    protected $tags;

	public function __construct()
	{
	    $this->songbooks = new ArrayCollection();
        $this->tags = new ArrayCollection();
	}

	/**
	 * Removes all songbooks.
	 */
	public function clearSongbooks()
	{
		$this->songbooks->clear();
	}

    /**
     * Removes all tags.
     */
    public function clearTags()
    {
        $this->tags->clear();
    }
}
