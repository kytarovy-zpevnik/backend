<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Recommendation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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

