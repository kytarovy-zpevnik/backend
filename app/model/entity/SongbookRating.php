<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
 *
 * SongbookRating entity.
 * @author Tomáš Jirásek
 */

class SongbookRating extends Rating
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inversedBy="songbookRatings")
     */
    protected $songbook;
}
