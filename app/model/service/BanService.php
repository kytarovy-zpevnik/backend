<?php

namespace App\Model\Service;

use App\Model\Entity\Ban;
use App\Model\Entity\User;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

/**
 * Ban service.
 * @author Tomáš Jirásek
 */
class BanService extends Object
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
     * @param string $comment
     * @return Ban
     */
    public function create($user, $comment)
    {
        $ban = new Ban();

        $ban->legitimate = true;
        $ban->comment    = $comment;
        $ban->createdOn  = new DateTime();
        $ban->user       = $user;

        $this->em->persist($ban);

        return $ban;
    }

    /**
     * @param $banId
     * @return Ban
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function delete($banId)
    {
        $ban = $this->em->find("Ban",$banId);
        if ($ban) {
            $this->em->remove($ban);
        }
        return $ban;
    }

    /**
     * @param $banId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @internal param int $banID
     * @return Ban
     */
    public function deactivateBan($banId)
    {
        $ban = $this->em->find("Ban",$banId);
        if ($ban) {
            $ban->legitimate = false;
        }
        return $ban;
    }

}
