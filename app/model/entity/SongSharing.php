<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 *
 * SongSharing entity.
 * @author Jiří Mantlík
 */

class SongSharing extends Sharing
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songShares")
     */
    protected $song;
}