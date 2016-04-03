<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 * @property string $title
 * @property string $chords
 * @property string $lyrics
 * @property string $album
 * @property string $author
 * @property string $originalAuthor
 * @property int $year
 * @property SongTaking[] $songTakes
 *
 * SongCopy entity.
 * @author Jiří Mantlík
 */

class SongCopy extends Copy
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songCopies")
     */
    protected $song;

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
     * @var SongTaking[]
     * @ORM\OneToMany(targetEntity="App\Model\Entity\SongTaking", mappedBy="songCopy")
     */
    protected $songTakes;
}