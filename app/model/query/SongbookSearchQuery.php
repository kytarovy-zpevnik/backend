<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 * Performs search for songbooks which name contains search string.
 * @author Tomáš Markacz
 */
class SongbookSearchQuery extends QueryObject
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
		return $repository->createQueryBuilder()
			->select('s')
			->from(Songbook::getClassName(), 's')
			->andWhere('s.owner = :owner')
			->andWhere('s.name LIKE :query')
			->setParameter('owner', $this->user)
			->setParameter('query', "%$this->search%")
			->orderBy('s.name');
	}

}
