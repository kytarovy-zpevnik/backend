<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 *
 * SongRating entity.
 * @author Tomáš Jirásek
 */

class SongRating extends Rating
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="songRatings")
     */
    protected $song;
}
