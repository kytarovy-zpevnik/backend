<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for songbooks which name contains search string.
 * @author Tomáš Markacz
 */
class SongbookSearchQuery extends QueryObject
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
		return $repository->createQueryBuilder()
			->select('s')
			->from(Songbook::class, 's')
			->orWhere('s.name LIKE :query')
			->setParameter('query', "%$this->search%");
	}

}
