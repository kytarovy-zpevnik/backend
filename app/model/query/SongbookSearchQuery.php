<?php

namespace App\Model\Query;

use App\Model\Entity\Songbook;
use App\Model\Entity\User;
use Doctrine\ORM\Query\Expr\Orx;
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

    /** @var bool */
    private $public;

	/**
	 * @param User $user
	 * @param string $search
     * @param bool $public
	 */
	public function __construct($user, $search, $public)
	{
		$this->user   = $user;
		$this->search = $search;
        $this->public = $public;
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

        $qb = $repository->createQueryBuilder()
            ->select('s')
            ->from(Songbook::getClassName(), 's')
            ->leftJoin('s.tags', 't')
            ->andWhere('s.archived = 0')
            ->andWhere($or)
            ->setParameter('query', "%$this->search%")
            ->orderBy('s.title');

        if (!$this->public) {
            $or2 = new Orx([
                's.owner = :user',
                'st.user = :user'
            ]);
            $qb->leftJoin('s.songbookTakes', 'st')
                ->andWhere($or2)
                ->setParameter('user', $this->user);
        }
        else {
            $qb->andWhere('s.public = 1');
        }

        return $qb;
	}

}
