<?php

namespace App\Model\Service;

use App\Model\Entity\BadContent;
use App\Model\Entity\User;
use App\Model\Entity\Song;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

/**
 * BadContent service.
 * @author Tomáš Jirásek
 */
class BadContentService extends Object
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
     * @return BadContent
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
