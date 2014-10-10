<?php

namespace App\Model\Service;

use App\Model\Entity\Song;
use App\Model\Entity\User;
use DateTime;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 * Song service
 * @author Tomáš Jirásek
 */
class SongService extends Object
{

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $title
     * @param $song
     * @param User $owner
     * @param string $originalAuthor
     * @param string $album
     * @param string $author
     * @param bool $public
     * @param int $year
     * @return Song
     */
    public function create($title, $song, User $owner = null, $originalAuthor = "undefined",
                           $album = "undefined", $author = "undefined", $public = false,
                           $year = null)
    {
        $song = new Song();

        $song->title = $title;
        $song->song  = $song;

        $song->owner          = $owner;
        $song->originalAuthor = $originalAuthor;
        $song->album          = $album;
        $song->author         = $author;
        $song->public         = $public;
        $song->year           = $year;

        $song->created  = new DateTime();
        $song->modified = $song->created;
        $song->archived = false;
        $song->viewers  = null;
        $song->editors  = null;
        $song->tags     = null;

        $this->em->persist($song);

        return $song;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $song = $this->em->find("Song",$id);
        if ($song) {
            $this->em->remove($song);
        }
    }

    /**
     * @return \App\Model\Entity\Song[]
     */
    public function getAllSongs()
    {
        return $this->em->getDao(Song::class)->findAll();
    }

    /**
     * @return \App\Model\Entity\Song[]
     */
    public function getAllPublicSongs()
    {
        return $this->em->getDao(Song::class)->findBy(["public" => true]);
    }

}
