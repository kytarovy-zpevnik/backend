<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Songbook $songbook
 *
 * SongbookTaking entity.
 * @author Jiří Mantlík
 */

class SongbookTaking extends Taking
{
    /**
     * @var Songbook
     * @ORM\ManyToOne(targetEntity="App\Model\Entity\Songbook", inversedBy="songbookTakes")
     */
    protected $songbook;
}