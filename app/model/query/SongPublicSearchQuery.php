<?php

namespace App\Model\Query;

use App\Model\Entity\Song;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for public songs which metadata contains search string.
 * @author Tomáš Jirásek
 */
class SongPublicSearchQuery extends QueryObject
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
            's.title LIKE :query',
            's.album LIKE :query',
            's.author LIKE :query',
            's.originalAuthor LIKE :query',
            's.year LIKE :query',
            't.tag LIKE :query'
        ]);

		return $repository->createQueryBuilder()
			->select('s')
			->from(Song::getClassName(), 's')
            ->leftJoin('s.tags', 't')
            ->andWhere('s.public = 1')
            ->andWhere($or)
            ->setParameter('query', "%$this->search%")
            ->orderBy('s.title');
	}

}
