<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Comment;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 *
 * SongComment entity.
 * @author Tomáš Jirásek
 */

class SongComment extends Comment
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songComments")
     */
    protected $song;
}
