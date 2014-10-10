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
        $ban->created    = new DateTime();
        $ban->user       = $user;

        $this->em->persist($ban);

        return $ban;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $ban = $this->em->find("Ban",$id);
        if ($ban) {
            $this->em->remove($ban);
        }
    }

    /**
     * @param $id
     * @return Ban
     */
    public function deactivateBan($id)
    {
        $ban = $this->em->find("Ban",$id);
        if ($ban) {
            $ban->legitimate = false;
        }
        return $ban;
    }

}
