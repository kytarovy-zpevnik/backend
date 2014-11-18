<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
 *
 * SongbookSharing entity.
 * @author Jiří Mantlík
 */

class SongbookSharing extends Sharing
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inversedBy="songbookShares")
     */
    protected $songbook;
}