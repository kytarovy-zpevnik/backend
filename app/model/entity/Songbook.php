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
 * @property SongbookSharing[] $songbookShares
 * @property SongbookTaking[] $songbookTakes
 * @property Song[] $songs
 * @property SongbookRating[] $songbookRatings
 * @property SongbookComment[] $songbookComments
 * @property SongbookTag[] $tags
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
     * @var SongbookSharing[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookSharing", mappedBy="songbook")
     */
    protected $songbookShares;

    /**
     * @var SongbookTaking[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongbookTaking", mappedBy="songbook")
     */
    protected $songbookTakes;

    /**
     * @var SongSongbook[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongSongbook", mappedBy="songbook")
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
        $this->songs = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * Removes all songs.
     */
    public function clearSongs()
    {
        $this->songs->clear();
    }

    /**
     * Removes all tags.
     */
    public function clearTags()
    {
        $this->tags->clear();
    }

    /**
     * Counts average rating.
     */
    public function getAverageRating()
    {
        $average = 0;

        foreach ($this->songbookRatings as & $rating) {
            $average += $rating->rating;
        }

        $count = count($this->songbookRatings);

        if($count > 0)
            $average /= $count;

        return [
            'rating'      => $average,
            'numOfRating' => $count
        ];
    }

    /**
     * Return number of songs in songbook.
     */
    public function getNumOfSongs()
    {
        return count($this->songs);
    }
}
