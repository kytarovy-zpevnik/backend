<?php

namespace App\Model\Service;

use App\Model\Entity\Comment;
use App\Model\Entity\User;
use App\Model\Entity\Song;
use App\Model\Entity\Songbook;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

/**
 * Comment service.
 * @author Tomáš Jirásek
 */
class CommentService extends Object
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
     * @param User $user
     * @param Song $song
     * @param string Comment
     * @return Comment
     */
    public function create($user, $song, $comment)
    {
        $badContent = new BadContent();

        $badContent->createdOn = new DateTime();
        $badContent->user      = $user;
        $badContent->song      = $song;
        $badContent->comment   = $comment;

        $this->em->persist($badContent);

        return $badContent;
    }

}
