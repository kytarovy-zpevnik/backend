<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
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
