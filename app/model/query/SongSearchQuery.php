<?php

namespace App\Model\Query;

use App\Model\Entity\Song;
use App\Model\Entity\User;
use Doctrine\ORM\Query\Expr\Orx;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for songs which metadata contains search string.
 * @author Tomáš Markacz
 */
class SongSearchQuery extends QueryObject
{

	/** @var User */
	private $user;

	/** @var string */
	private $search;

	/**
	 * @param User $user
	 * @param string $search
	 */
	public function __construct(User $user, $search)
	{
		$this->user   = $user;
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
			->andWhere('s.owner = :owner')
			->andWhere($or)
			->setParameter('owner', $this->user)
			->setParameter('query', "%$this->search%")
			->orderBy('s.title');
	}

}
