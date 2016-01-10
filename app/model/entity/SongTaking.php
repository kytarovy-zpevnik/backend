<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
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
}