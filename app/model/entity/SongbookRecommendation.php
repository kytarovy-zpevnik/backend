<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Recommendation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * SongbookRecommendation entity.
 * @author Tomáš Jirásek
 */

class SongbookRecommendation extends Recommendation
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook")
     */
    protected $songbook;
}
