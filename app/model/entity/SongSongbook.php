<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="song_songbook",uniqueConstraints={@ORM\UniqueConstraint(name="pos_in_sb", columns={"songbook_id", "position"})})
 *
 * @property-read int $id
 * @property Song $song
 * @property Songbook $songbook
 * @property int $position
 *
 *
 * SongSongbook entity.
 * @author Jiří Mantlík
 */
class SongSongbook extends BaseEntity {

    use Identifier;


    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songbooks")
     * */
    protected $song;

    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inversedBy="songs")
     * */
    protected $songbook;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $position;

} 