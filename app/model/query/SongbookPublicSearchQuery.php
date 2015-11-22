<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for public songboks which metadata contains search string.
 * @author Jiří Mantlík
 */
class SongbookPublicSearchQuery extends QueryObject
{

    /** @var string */
    private $search;

    /**
     * @param string $search
     */
    public function __construct($search)
    {
        $this->search = $search;
    }

    /**
     * @param Queryable $repository
     * @return QueryBuilder
     */
    protected function doCreateQuery(Queryable $repository)
    {

        $or = new Orx([
            's.name LIKE :query',
            't.tag LIKE :query'
        ]);

        return $repository->createQueryBuilder()
            ->select('s')
            ->from(Songbook::getClassName(), 's')
            ->leftJoin('s.tags', 't')
            ->andWhere('s.public = 1')
            ->andWhere($or)
            ->setParameter('query', "%$this->search%")
            ->orderBy('s.name');
    }

}
