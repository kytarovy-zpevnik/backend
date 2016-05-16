<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs advanced search in metadata for songbooks.
 * @author Jiří Mantlík
 */
class SongbookAdvSearchQuery extends QueryObject
{

    /** @var User */
    private $user;

    /** @var string */
    private $name;

    /** @var string */
    private $owner;

    /** @var string */
    private $tag;

    /** @var bool */
    private $public;

    /**
     * @param User $user
     * @param $name
     * @param $owner
     * @param $tag
     * @param bool $public
     */
    public function __construct($user, $name, $owner, $tag, $public)
    {
        $this->user   = $user;
        $this->name  = $name;
        $this->owner  = $owner;
        $this->tag    = $tag;
        $this->public = $public;
    }

    /**
     * @param Queryable $repository
     * @return QueryBuilder
     */
    protected function doCreateQuery(Queryable $repository)
    {
        $query = $repository->createQueryBuilder()
            ->select('s')
            ->from(Songbook::getClassName(), 's')
            ->andWhere('s.archived = 0')
            ->orderBy('s.name');

        if (!$this->public) {
            $or = new Orx([
                's.owner = :user',
                'st.user = :user'
            ]);
            $query->leftJoin('s.songbookTakes', 'st')
                ->andWhere($or)
                ->setParameter('user', $this->user);
        }
        else {
            $query->andWhere('s.public = 1');
        }

        if ($this->name != null)
            $query->andWhere('s.name LIKE :name')
                ->setParameter('name', "%$this->name%");

        if ($this->owner != null) {
            $query->innerJoin('s.owner', 'u')
                ->andWhere('u.username LIKE :owner')
                ->setParameter('owner', "%$this->owner%");
        }

        if ($this->tag != null) {
            $query->innerJoin('s.tags', 't')
                ->andWhere('t.tag LIKE :tag')
                ->setParameter('tag', "%$this->tag%");
        }

        return $query;
    }

}
