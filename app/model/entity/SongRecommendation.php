<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Song $song
 *
 * SongRecommendation entity.
 * @author Tomáš Jirásek
 */

class SongRecommendation extends Recommendation
{
    /**
     * @var Song
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Song")
     */
    protected $song;
}

