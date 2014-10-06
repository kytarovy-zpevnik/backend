<?php

namespace App\Model\Entity;

use Kdyby\Doctrine\Entities\Comment;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
 *
 * SongbookComment entity.
 * @author Tomáš Jirásek
 */

class SongbookComment extends Comment
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inverseBy="songbookComments")
     */
    protected $songbook;
}
