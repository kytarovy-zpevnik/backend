<?php

namespace App\Model\Query;

use App\Model\Entity\Song;
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
		return $repository->createQueryBuilder()
			->select('s')
			->from(Song::class, 's')
            ->orWhere('s.title LIKE :query')
            ->orWhere('s.author LIKE :query')
            ->orWhere('s.originalAuthor LIKE :query')
			->setParameter('query', "%$this->search%")
			->orderBy('s.title');
	}

}
