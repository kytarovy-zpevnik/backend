<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use App\Model\Entity\User;
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
    private $tag;

    /** @var bool */
    private $public;

    /**
     * @param User $user
     * @param $name
     * @param $tag
     * @param bool $public
     */
    public function __construct($user, $name, $tag, $public)
    {
        $this->user   = $user;
        $this->name  = $name;
        $this->tag    = $tag;
        $this->public = $public;
    }

    /**
     * @param Queryable $repository
     * @return QueryBuilder
     */
    protected function doCreateQuery(Queryable $repository)
    {
        $condition = $this->public ? 's.public = 1' : 's.owner = :owner';

        $query = $repository->createQueryBuilder()
            ->select('s')
            ->from(Songbook::getClassName(), 's')
            ->andWhere($condition)
            ->orderBy('s.name');

        if ($this->name != null)
            $query->andWhere('s.name LIKE :name')->setParameter('name', "%$this->name%");

        if ($this->tag != null) {
            $query->innerJoin('s.tags', 't')->andWhere('t.tag LIKE :tag')->setParameter('tag', "%$this->tag%");
        }

        if(!$this->public)
            $query->setParameter('owner', $this->user);

        return $query;
    }

}
