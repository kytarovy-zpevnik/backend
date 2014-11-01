<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 *
 * SongTag entity.
 * @author Tomáš Jirásek
 */

class SongTag extends Tag
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song", inversedBy="tags")
     */
    protected $song;
}
