<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
 *
 * SongbookTag entity.
 * @author Tomáš Jirásek
 */

class SongbookTag extends Tag
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inversedBy="tags")
     */
    protected $songbook;
}
