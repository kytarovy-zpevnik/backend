<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 * @property SongCopy $songCopy
 *
 * SongTaking entity.
 * @author Jiří Mantlík
 */

class SongTaking extends Taking
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songTakes")
     */
    protected $song;

    /**
     * @var SongCopy
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\SongCopy", inversedBy="songTakes")
     */
    protected $songCopy;
}